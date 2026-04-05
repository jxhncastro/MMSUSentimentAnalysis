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
                $totalScanned++;
                
                // 1. Clean Headers
                $row = [];
                foreach ($record as $key => $value) {
                    $cleanKey = strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $key)));
                    $row[$cleanKey] = $value;
                }
                
                // 2. Extract Year from "Completion time"
                // Standard format found in your CSV: "1/2/2024  6:37:51 PM"
                $year = now()->year; 
                $dateValue = $row['completion time'] ?? $row['start time'] ?? null;

                if (!empty($dateValue)) {
                    try {
                        // Normalize whitespace (replaces non-breaking spaces or double spaces with a single space)
                        $normalizedDate = preg_replace('/\s+/', ' ', trim($dateValue));
                        
                        // Explicitly parse the format M/d/Y h:i:s A
                        // 'n' = month (1-12), 'j' = day (1-31), 'Y' = year (2024), 'g' = hour (1-12), 'i' = min, 's' = sec, 'A' = AM/PM
                        $year = Carbon::createFromFormat('n/j/Y g:i:s A', $normalizedDate)->year;
                    } catch (\Exception $e) {
                        try {
                            // Fallback to standard Carbon parsing
                            $year = Carbon::parse($dateValue)->year;
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for: " . $dateValue);
                        }
                    }
                }

                // 3. Extract Office/Unit
                $office = $this->findUnitColumn($row);

                // 4. Extract and Clean Services
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

                // 5. Extract Raw Feedback Text
                $rawText = $row['suggestions on how we can further improve our services'] 
                            ?? $row['suggestions'] 
                            ?? $row['comments'] 
                            ?? $row['feedback'] 
                            ?? end($record);
                
                $cleanText = $this->cleanText($rawText);

                // 6. DATA CLEANING & COUNTING
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

                // Update progress every 10 rows
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
                    'comment'           => $cleanText,
                    'sentiment'         => ucfirst($analysis['sentiment']),
                    'topic'             => $analysis['theme'] ?? $analysis['aspect'] ?? 'General',
                    'confidence'        => $this->getConfidenceTier($analysis['confidence']),
                    'method'            => $analysis['method'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                if (count($batchData) >= 50) {
                    Feedback::insert($batchData);
                    $savedCount += count($batchData);
                    $batchData = [];
                }
            }

            if (!empty($batchData)) {
                Feedback::insert($batchData);
                $savedCount += count($batchData);
            }

            // 8. FINAL COMPLETION SYNC
            $batch->update([
                'status'             => 'completed', 
                'processed_rows'     => $totalScanned,
                'blank_count'        => $blankCount,
                'na_count'           => $naCount,
                'special_char_count' => $specialCharCount,
                'valid_count'        => $validCount
            ]);

            Log::info("JOB DONE: Raw: {$totalRawInCsv}, Valid: {$validCount}, Saved: {$savedCount}");

        } catch (\Exception $e) {
            Log::error("SENTIMENT JOB ERROR: " . $e->getMessage());
            $batch->update(['status' => 'failed']);
        }
    }

    private function analyzeWithAI($text, $aspect)
    {
        try {
            $baseUrl = env('AI_MODEL_URL', 'http://localhost:5000');
            $response = Http::timeout(25)->post($baseUrl . '/predict', [
                'text' => $text,
                'aspect' => $aspect
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("AI API Unreachable: " . $e->getMessage());
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