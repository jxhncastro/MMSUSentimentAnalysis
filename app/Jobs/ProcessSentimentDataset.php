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
            
            // FIX: Handle potential BOM (Byte Order Mark) from Excel CSVs
            $csv->setHeaderOffset(0);
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

                // 3. Extract Comment (Brute Force Strategy)
                $rawText = $row['suggestions on how we can further improve our services'] 
                           ?? $row['suggestions'] 
                           ?? $row['comments'] 
                           ?? $row['feedback'] 
                           ?? end($record); // Last resort: Take the last column
                
                $cleanText = $this->cleanText($rawText);

                // 4. Debugging Log (First 5 rows)
                if ($totalScanned <= 5) {
                    Log::info("Row {$totalScanned} | Office: {$office} | Text Found: " . substr($cleanText, 0, 50));
                }

                // 5. SKIP logic
                if (empty($cleanText) || strlen($cleanText) < 2 || in_array(strtolower($cleanText), $this->garbagePhrases)) {
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
                    'topic'             => $analysis['aspect'] ?? 'General',
                    'confidence'        => $this->getConfidenceTier($analysis['confidence']),
                    'method'            => $analysis['method'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                if (count($batchData) >= 50) {
                    Feedback::insert($batchData);
                    $savedCount += count($batchData);
                    $batch->update(['processed_rows' => $totalScanned]);
                    $batchData = [];
                }
            }

            if (!empty($batchData)) {
                Feedback::insert($batchData);
                $savedCount += count($batchData);
            }

            $batch->update(['status' => 'completed', 'processed_rows' => $totalScanned]);
            Log::info("JOB DONE: Scanned {$totalScanned}, Saved {$savedCount}");

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
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function getConfidenceTier($score) {
        if ($score >= 80) return 'High';
        if ($score >= 50) return 'Medium';
        return 'Low';
    }
}