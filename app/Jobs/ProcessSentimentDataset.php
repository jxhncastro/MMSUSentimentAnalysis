<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Models\AnalysisBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use League\Csv\Reader;

class ProcessSentimentDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0; // Infinite timeout prevents the job from dying during 8k processing
    protected $filePath;
    protected $batchId;

    public function __construct($filePath, $batchId = null)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
    }

    public function handle()
    {
        // 1. Read the CSV File
        if (!file_exists($this->filePath)) {
            \Log::error("CSV File not found: " . $this->filePath);
            return;
        }

        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0); // Assumes first row is header
        
        // Convert to array so we can chunk it
        $records = iterator_to_array($csv->getRecords());
        $batch = AnalysisBatch::find($this->batchId);

        // 2. Process in Chunks of 50 (Batch Optimization)
        // Sending 50 rows at once is 10x faster than 1-by-1
        $chunks = array_chunk($records, 50);

        foreach ($chunks as $chunk) {
            $payload = [];
            
            // Prepare the payload for the Python API
            foreach ($chunk as $record) {
                $payload[] = [
                    'text' => $record['feedback_text'] ?? $record['comment'] ?? '',
                    'aspect' => $record['aspect'] ?? 'General'
                ];
            }

            try {
                // 3. Call the Python Batch Endpoint
                // We use a longer timeout (120s) to give the AI time to think about 50 sentences
                $response = Http::withHeaders(['ngrok-skip-browser-warning' => 'true'])
                    ->timeout(120) 
                    ->post(env('AI_MODEL_URL') . '/predict_batch', [
                        'items' => $payload
                    ]);

                if ($response->successful()) {
                    $results = $response->json();
                    
                    $dataToInsert = [];
                    $timestamp = now();

                    // Map the AI results to your Database Columns
                    foreach ($results as $index => $res) {
                        // Use the original text from the chunk to ensure accuracy
                        $originalText = $chunk[$index]['feedback_text'] ?? $chunk[$index]['comment'] ?? '';

                        $dataToInsert[] = [
                            'raw_text'   => $originalText,
                            'aspect'     => $res['aspect_used'],
                            'sentiment'  => $res['sentiment'],
                            'confidence' => $res['confidence'],
                            'method'     => $res['method'], // e.g., 'heuristic_negative_rule' or 'transformer_v2'
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }

                    // 4. Bulk Insert (Much lighter on the database)
                    Feedback::insert($dataToInsert);

                    // 5. Update Progress Bar
                    if ($batch) {
                        $batch->increment('processed_rows', count($chunk));
                    }
                } else {
                    \Log::error("Batch AI Error: " . $response->body());
                }

            } catch (\Exception $e) {
                \Log::error("Batch Processing Exception: " . $e->getMessage());
                // We continue to the next chunk even if one fails
                continue; 
            }
        }

        // 6. Finish Up
        if ($batch) {
            $batch->update(['status' => 'completed']);
        }

        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}