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

    // Phrases that represent empty or non-useful feedback
    protected $garbagePhrases = [
        'none', 'n a', 'na', 'no', 'nothing', 'no comment', 'no suggestions',
        'nil', 'n/a', 'none.', 'none po', 'nons', 'n/q', 'nne', 'notjing', 
        'nonia', 'nonw', 'n/aa', 'n aa', 'wala', 'wala po', 'meron', 
        'wala naman', 'awan', 'awan po', 'awanen', 'noise'
    ];

    // CRITICAL: Strings that are NOT offices and should be filtered out
    protected $invalidUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'neutral', 'n/a', 'na', 
        'none', 'select office', 'others', 'unknown'
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
            $records = $csv->getRecords();

            $batchData = [];
            $totalScanned = 0;

            foreach ($records as $record) {
                $totalScanned++;
                
                // Keys become lowercase: e.g., 'office/unit visited'
                $row = array_change_key_case($record, CASE_LOWER);
                
                // 1. Extract and Validate the Office/Unit
                $unit = $this->findUnitColumn($row);

                // 2. Clean and Validate Feedback Text
                $rawText = $row['comments'] ?? $row['feedback'] ?? $row['suggestions'] ?? '';
                $cleanText = $this->cleanText($rawText);

                // Skip processing if feedback is garbage or too short
                if (empty($cleanText) || strlen($cleanText) < 2 || in_array($cleanText, $this->garbagePhrases)) {
                    if ($totalScanned % 50 == 0) $batch->update(['processed_rows' => $totalScanned]);
                    continue; 
                }

                // 3. AI Sentiment Analysis
                $analysis = $this->analyzeWithAI($cleanText, $unit);

                $batchData[] = [
                    'analysis_batch_id' => $this->batchId,
                    'operating_unit'    => trim($unit),
                    'feedback_text'     => $cleanText,
                    'sentiment'         => $analysis['sentiment'],
                    'confidence'        => $analysis['confidence'],
                    'method'            => $analysis['method'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                // Batch insert for better performance
                if (count($batchData) >= 50) {
                    Feedback::insert($batchData);
                    $batch->update(['processed_rows' => $totalScanned]);
                    $batchData = [];
                }
            }

            if (!empty($batchData)) Feedback::insert($batchData);

            $batch->update(['status' => 'completed', 'processed_rows' => $batch->total_rows]);

        } catch (\Exception $e) {
            Log::error("Sentiment Job Failed: " . $e->getMessage());
            $batch->update(['status' => 'failed']);
        }
    }

    private function analyzeWithAI($text, $aspect)
    {
        try {
            // Use config() or env() to grab the URL from your .env file
            $baseUrl = env('AI_MODEL_URL', 'http://localhost:5000');
            
            $response = Http::timeout(15)->post($baseUrl . '/predict', [
                'text' => $text,
                'aspect' => $aspect
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("AI API Offline: " . $e->getMessage());
        }

        return ['sentiment' => 'Neutral', 'confidence' => 0, 'method' => 'Fallback_Offline'];
    }

    private function findUnitColumn($row) {
        // 1. Target the exact column from your CSV (header: Office/Unit Visited)
        // array_change_key_case makes this 'office/unit visited'
        $val = trim($row['office/unit visited'] ?? $row['office'] ?? '');

        // 2. List of survey words to ignore
        $surveyRatings = [
            'strongly agree', 'agree', 'neither agree nor disagree', 
            'disagree', 'strongly disagree', 'n/a', 'na', 'neutral'
        ];

        // 3. Validation: If it's a rating, an empty string, or too short, fallback to General Service
        if (empty($val) || in_array(strtolower($val), $surveyRatings) || strlen($val) < 2) {
            return 'General Service';
        }

        return $val;
    }

    private function cleanText($text)
    {
        if (empty($text)) return "";
        $text = strtolower($text);
        // Remove emojis and non-standard characters
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        $text = preg_replace("/[^a-z0-9\s\!\?\.]/i", " ", $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}