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

            // --- 1. PRE-SCAN FOR TOTAL RAW COUNT ---
            // This ensures the dashboard shows the total CSV lines immediately
            $totalRawInCsv = count($csv); 
            $batch->update([
                'total_rows' => $totalRawInCsv, // Ensure you have this column in your migration
                'status' => 'processing'
            ]);

            $records = $csv->getRecords();
            $batchData = [];
            $totalScanned = 0;
            $savedCount = 0;

            foreach ($records as $record) {
                $totalScanned++;
                
                // Clean keys: lowercase, trim, and remove non-printable characters
                $row = [];
                foreach ($record as $key => $value) {
                    $cleanKey = strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $key)));
                    $row[$cleanKey] = $value;
                }
                
                if ($totalScanned === 1) {
                    Log::info("DEBUG: Cleaned Headers: " . implode('|', array_keys($row)));
                }

                // 1. Extract Office
                $office = $this->findUnitColumn($row);

                // 2. Consolidate Services
                $services = [];
                for ($i = 1; $i <= 44; $i++) {
                    $key = ($i === 1) ? 'service/s availed' : "service/s availed{$i}";
                    if (!empty($row[$key])) {
                        $val = trim($row[$key]);
                        if ($val !== '' && !in_array(strtolower($val), ['n/a', 'na', 'none'])) {
                            $services[] = $val;
                        }
                    }
                }
                $combinedServices = !empty($services) ? implode(', ', array_unique($services)) : 'Unspecified';

                // 3. Extract Comment
                $rawText = $row['suggestions on how we can further improve our services'] 
                            ?? $row['suggestions'] 
                            ?? $row['comments'] 
                            ?? $row['feedback'] 
                            ?? end($record);
                
                $cleanText = $this->cleanText($rawText);

                // 5. SKIP logic (We still count it as 'scanned' for progress, but don't save)
                if (empty($cleanText) || strlen($cleanText) < 2 || in_array(strtolower($cleanText), $this->garbagePhrases)) {
                    // Update progress even on skipped rows so the UI moves
                    if ($totalScanned % 10 === 0) {
                        $batch->update(['processed_rows' => $totalScanned]);
                    }
                    continue; 
                }

                // 6. AI Sentiment Analysis
                $analysis = $this->analyzeWithAI($cleanText, $office);

                $batchData[] = [
                    'analysis_batch_id' => $this->batchId,
                    'office'            => trim($office),
                    'services_availed'  => $combinedServices,
                    'comment'           => $cleanText,
                    'sentiment'         => ucfirst($analysis['sentiment']),
                    'topic'             => $analysis['theme'] ?? $analysis['aspect'] ?? 'General',
                    'confidence'        => $this->getConfidenceTier($analysis['confidence']),
                    'method'            => $analysis['method'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                // Chunked Insert for performance
                if (count($batchData) >= 50) {
                    Feedback::insert($batchData);
                    $savedCount += count($batchData);
                    $batch->update(['processed_rows' => $totalScanned]);
                    $batchData = [];
                }
            }

            // Final insert for remaining records
            if (!empty($batchData)) {
                Feedback::insert($batchData);
                $savedCount += count($batchData);
            }

            $batch->update([
                'status' => 'completed', 
                'processed_rows' => $totalScanned
            ]);

            Log::info("JOB DONE: Total CSV Rows: {$totalRawInCsv}, Saved to DB: {$savedCount}");

        } catch (\Exception $e) {
            Log::error("SENTIMENT JOB ERROR: " . $e->getMessage());
            $batch->update(['status' => 'failed']);
        }
    }

    private function analyzeWithAI($text, $aspect)
    {
        try {
            $baseUrl = env('AI_MODEL_URL', 'http://localhost:5000');
            $response = Http::timeout(15)->post($baseUrl . '/predict', [
                'text' => $text,
                'aspect' => $aspect
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("Colab AI Unreachable: " . $e->getMessage());
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
        // Remove emojis for the model
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function getConfidenceTier($score) {
        // Handle both 0-1 and 0-100 scales
        $normalized = $score <= 1 ? $score * 100 : $score;
        if ($normalized >= 80) return 'High';
        if ($normalized >= 50) return 'Medium';
        return 'Low';
    }
}