<?php

namespace App\Jobs;

use App\Models\AnalysisBatch;
use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Carbon\Carbon;

class ProcessSentimentDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; 
    protected $filePath;
    protected $batchId;

    protected $garbagePhrases = [
        'none', 'n a', 'na', 'no', 'nothing', 'no comment', 'no suggestions',
        'nil', 'n/a', 'none.', 'none po', 'nons', 'n/q', 'nne', 'notjing', 
        'nonia', 'nonw', 'n/aa', 'n aa', 'wala', 'wala po', 'meron', 
        'wala naman', 'awan', 'awan po', 'awanen', 'noise', 'neutral'
    ];

    public function __construct($filePath, $batchId)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
    }

    public function handle()
    {
        $batch = AnalysisBatch::find($this->batchId);
        if (!$batch) return;

        try {
            $csv = Reader::createFromPath($this->filePath, 'r');
            $csv->setHeaderOffset(0);

            $totalRawInCsv = count($csv); 
            
            $batch->update([
                'total_rows' => $totalRawInCsv,
                'status' => 'processing',
                'processed_rows' => 0,
                'blank_count' => 0,
                'na_count' => 0,
                'special_char_count' => 0,
                'valid_count' => 0
            ]);

            $records = $csv->getRecords();
            $batchData = [];
            
            $totalScanned = 0;
            $savedCount = 0;
            $blankCount = 0;
            $naCount = 0;
            $specialCharCount = 0;
            $validCount = 0;

            foreach ($records as $record) {
                // WRAP EVERY ROW IN A TRY-CATCH so one bad row doesn't kill the whole 407-row job
                try {
                    $totalScanned++;
                    
                    // 1. Aggressive Header Cleaning
                    $row = [];
                    foreach ($record as $key => $value) {
                        // Remove all non-printable and non-ascii to fix hidden Excel chars
                        $cleanKey = strtolower(trim(preg_replace('/[^[:print:]]/', '', $key)));
                        $row[$cleanKey] = $value;
                    }
                    
                    // 2. Extract Year
                    $year = now()->year; 
                    $dateValue = $row['completion time'] ?? $row['start time'] ?? null;

                    if (!empty($dateValue)) {
                        try {
                            $normalizedDate = preg_replace('/\s+/', ' ', trim($dateValue));
                            $year = Carbon::parse($normalizedDate)->year;
                        } catch (\Exception $e) {
                            Log::warning("Date parse failed for: " . $dateValue);
                        }
                    }

                    // 3. Extract Office/Unit
                    $office = $this->findUnitColumn($row);

                    // 4. Robust Services Extraction
                    $services = [];
                    foreach($row as $k => $v) {
                        if (str_contains($k, 'service') && str_contains($k, 'avail')) {
                            $val = trim($v);
                            if (!empty($val) && !in_array(strtolower($val), ['n/a', 'na', 'none'])) {
                                $services[] = $val;
                            }
                        }
                    }
                    $combinedServices = !empty($services) ? implode(', ', array_unique($services)) : 'Unspecified';

                    // 5. Extract Raw Feedback Text
                    $rawText = $row['suggestions on how we can further improve our services'] 
                                ?? $row['suggestions on how we can further improve our service/s']
                                ?? $row['suggestions'] 
                                ?? $row['comments'] 
                                ?? $row['feedback'] 
                                ?? end($record);
                    
                    $cleanText = $this->cleanText($rawText);

                    // 6. Data Cleaning & Counting
                    $isSkipped = true;
                    if (empty($rawText) || trim($rawText) === '') {
                        $blankCount++;
                    } elseif (in_array(strtolower($cleanText), $this->garbagePhrases)) {
                        $naCount++;
                    } elseif (strlen(preg_replace('/[^a-zA-Z0-9]/', '', $cleanText)) === 0) {
                        $specialCharCount++;
                    } elseif (strlen($cleanText) < 2) {
                        $specialCharCount++; 
                    } else {
                        $isSkipped = false;
                        $validCount++;
                    }

                    // Sync progress to DB every 10 rows
                    if ($totalScanned % 10 === 0) {
                        $batch->update([
                            'processed_rows' => $totalScanned,
                            'blank_count' => $blankCount,
                            'na_count' => $naCount,
                            'special_char_count' => $specialCharCount,
                            'valid_count' => $validCount
                        ]);
                    }

                    if ($isSkipped) continue; 

                    // 7. AI Sentiment Analysis
                    $analysis = $this->analyzeWithAI($cleanText, $office);

                    $batchData[] = [
                        'analysis_batch_id' => $this->batchId,
                        'office'            => trim($office),
                        'year'              => $year, 
                        'services_availed'  => $combinedServices,
                        'comment'           => mb_strcut($cleanText, 0, 500), // Prevent DB overflow
                        'sentiment'         => ucfirst($analysis['sentiment']),
                        'topic'             => $analysis['theme'] ?? $analysis['aspect'] ?? 'General',
                        'confidence'        => $this->getConfidenceTier($analysis['confidence']),
                        'method'            => $analysis['method'],
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];

                    // Insert in small chunks to keep memory low
                    if (count($batchData) >= 5) {
                        Feedback::insert($batchData);
                        $savedCount += count($batchData);
                        $batchData = [];
                    }

                } catch (\Exception $rowException) {
                    Log::error("Error on Row {$totalScanned}: " . $rowException->getMessage());
                    continue; // Skip the bad row and keep going!
                }
            }

            // Final batch insert
            if (!empty($batchData)) {
                Feedback::insert($batchData);
                $savedCount += count($batchData);
            }

            // 8. Final Completion
            $batch->update([
                'status'             => 'completed', 
                'processed_rows'     => $totalScanned,
                'blank_count'        => $blankCount,
                'na_count'           => $naCount,
                'special_char_count' => $specialCharCount,
                'valid_count'        => $validCount
            ]);

            Log::info("JOB FINISHED: Total: {$totalScanned}, Saved: {$savedCount}");

        } catch (\Exception $e) {
            Log::error("CRITICAL JOB FAILURE: " . $e->getMessage());
            $batch->update(['status' => 'failed']);
        }
    }

    private function analyzeWithAI($text, $aspect)
    {
        try {
            $baseUrl = env('AI_MODEL_URL', 'http://localhost:5000');
            $response = Http::timeout(45)->post($baseUrl . '/predict', [
                'text' => $text,
                'aspect' => $aspect
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("AI API Unreachable for text: " . substr($text, 0, 20));
        }
        return ['sentiment' => 'Neutral', 'confidence' => 0, 'method' => 'Fallback_Offline'];
    }

    private function findUnitColumn($row) {
        $val = trim(
            $row['office/unit visited'] ?? 
            $row['operating unit'] ?? 
            $row['unit'] ?? 
            $row['office'] ?? ''
        );

        $surveyRatings = ['strongly agree', 'agree', 'neither agree nor disagree', 'disagree', 'strongly disagree', 'n/a', 'na', 'neutral'];

        if (empty($val) || in_array(strtolower($val), $surveyRatings) || strlen($val) < 2) {
            return 'General Service';
        }
        return $val;
    }

    private function cleanText($text)
    {
        if (empty($text)) return "";
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function getConfidenceTier($score) {
        $normalized = $score <= 1 ? $score * 100 : $score;
        if ($normalized >= 80) return 'High';
        if ($normalized >= 50) return 'Medium';
        return 'Low';
    }
}