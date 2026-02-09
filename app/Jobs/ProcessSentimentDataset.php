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

    public $timeout = 3600; // 1 Hour for large datasets

    protected $filePath;
    protected $batchId;

    // Translated from your Python Script
    protected $garbagePhrases = [
        'none', 'n a', 'na', 'no', 'nothing', 'no comment', 'no suggestions',
        'nil', 'n/a', 'none.', 'none po', 'nons', 'n/q', 'nne', 'notjing', 
        'nonia', 'nonw', 'n/aa', 'n aa', 'wala', 'wala po', 'meron', 
        'wala naman', 'awan', 'awan po', 'awanen', 'noise'
    ];

    public function __construct($filePath, $batchId)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
    }

    public function handle()
    {
        $batch = AnalysisBatch::find($this->batchId);
        Log::info("🚀 PROCESSING BATCH #{$this->batchId}");

        try {
            if (!file_exists($this->filePath)) {
                Log::error("❌ File not found: {$this->filePath}");
                if ($batch) $batch->update(['status' => 'failed']);
                return;
            }

            // 1. READ CSV & FIX BOM (Invisible Characters)
            $csv = Reader::createFromPath($this->filePath, 'r');
            $csv->setHeaderOffset(0); 
            if (method_exists($csv, 'skipInputBOM')) {
                $csv->skipInputBOM(); 
            }

            $records = $csv->getRecords();
            $batchData = [];
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($records as $index => $record) {
                // 2. DYNAMIC COLUMN MAPPING (Finds "Suggestion" and "Office")
                $row = array_change_key_case($record, CASE_LOWER);
                $rawText = null;
                $unit = 'General';

                foreach ($row as $key => $value) {
                    if (str_contains($key, 'suggestion') || str_contains($key, 'improve') || str_contains($key, 'feedback')) {
                        $rawText = $value;
                    }
                    if (str_contains($key, 'office') || str_contains($key, 'unit')) {
                        $unit = $value;
                    }
                }

                // 3. APPLY "STRICT CLEANING" (PHP Version of your Python function)
                $cleanText = $this->cleanText($rawText);

                // 4. GARBAGE FILTER
                // If text is empty, too short, or in the garbage list -> SKIP IT
                if (empty($cleanText) || strlen($cleanText) < 3 || in_array($cleanText, $this->garbagePhrases)) {
                    $skippedCount++;
                    continue; 
                }

                // --- AI ANALYSIS ---
                $sentiment = 'Neutral';
                $confidence = 0.0;
                $method = 'Heuristic';

                try {
                    $response = Http::timeout(5)->post(env('AI_MODEL_URL') . '/predict', [
                        'text' => $cleanText,
                        'aspect' => $unit
                    ]);

                    if ($response->successful()) {
                        $pred = $response->json();
                        $sentiment = ucfirst($pred['sentiment']);
                        $confidence = ($pred['confidence_score'] ?? 0) * 100;
                        $method = 'AI Model';
                    }
                } catch (\Exception $e) {
                    // Fail silently, save as Neutral so we don't lose data
                    $method = 'Fallback';
                }

                $batchData[] = [
                    'operating_unit' => $unit,
                    'feedback_text'  => mb_convert_encoding($cleanText, 'UTF-8', 'UTF-8'),
                    'sentiment'      => $sentiment,
                    'confidence'     => $confidence,
                    'method'         => $method,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                // BATCH INSERT (Every 50 rows)
                if (count($batchData) >= 50) {
                    Feedback::insert($batchData);
                    $processedCount += count($batchData);
                    if ($batch) $batch->update(['processed_rows' => $processedCount]);
                    $batchData = [];
                    Log::info("✅ Saved {$processedCount} rows...");
                }
            }

            // Insert Remaining
            if (!empty($batchData)) {
                Feedback::insert($batchData);
                $processedCount += count($batchData);
            }

            if ($batch) {
                $batch->update([
                    'status' => 'completed', 
                    'processed_rows' => $processedCount
                ]);
            }
            Log::info("🏁 DONE. Processed: {$processedCount}, Skipped Garbage: {$skippedCount}");

        } catch (\Exception $e) {
            Log::error("❌ JOB FAILED: " . $e->getMessage());
            if ($batch) $batch->update(['status' => 'failed']);
        }
    }

    /**
     * PHP version of your 'strict_clean' Python function
     */
    private function cleanText($text)
    {
        if (empty($text)) return "";

        // Convert to lowercase
        $text = strtolower($text);

        // Remove emojis (Basic Regex)
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);

        // Expand simple contractions
        $text = preg_replace('/\b(r)u\b/', 'are you', $text);
        $text = preg_replace('/\b(u)\b/', 'you', $text);

        // Remove special chars but keep punctuation !, ?, . (Like your script)
        $text = preg_replace("/[^a-z0-9\s\!\?\.]/i", " ", $text);

        // Collapse repeating chars (slowww -> slow)
        $text = preg_replace('/(.)\1{2,}/', '$1', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}