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
    if (!$batch) return;

    try {
        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $batchData = [];
        $totalScanned = 0; // Tracks progress (Saved + Skipped)
        $savedCount = 0;   // Tracks actual DB inserts

        foreach ($records as $record) {
            $totalScanned++; // 1. Move progress for EVERY row
            
            $row = array_change_key_case($record, CASE_LOWER);
            $rawText = $this->findFeedbackColumn($row);
            $cleanText = $this->cleanText($rawText);

            // 2. If it's garbage, skip but update progress occasionally
            if (empty($cleanText) || strlen($cleanText) < 2 || in_array($cleanText, $this->garbagePhrases)) {
                if ($totalScanned % 50 == 0) {
                    $batch->update(['processed_rows' => $totalScanned]);
                }
                continue; 
            }

            // ... AI Analysis Logic Here ...

            $batchData[] = [
                'feedback_text' => $cleanText,
                'sentiment' => $sentiment,
                // ... other fields ...
            ];

            // 3. Update progress when saving chunks
            if (count($batchData) >= 50) {
                Feedback::insert($batchData);
                $savedCount += count($batchData);
                $batch->update(['processed_rows' => $totalScanned]); // Use scanned count!
                $batchData = [];
            }
        }

        // 4. Final sync to reach 100%
        if (!empty($batchData)) {
            Feedback::insert($batchData);
        }

        $batch->update([
            'status' => 'completed',
            'processed_rows' => $batch->total_rows // Force 100%
        ]);

    } catch (\Exception $e) {
        Log::error("Job Failed: " . $e->getMessage());
        $batch->update(['status' => 'failed']);
    }
}

    private function cleanText($text)
    {
        if (empty($text)) return "";
        $text = strtolower($text);
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        $text = preg_replace('/\b(r)u\b/', 'are you', $text);
        $text = preg_replace('/\b(u)\b/', 'you', $text);
        $text = preg_replace("/[^a-z0-9\s\!\?\.]/i", " ", $text);
        $text = preg_replace('/(.)\1{2,}/', '$1', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}