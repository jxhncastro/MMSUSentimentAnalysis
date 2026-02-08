<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback; // Ensure you have this model
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DatasetController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Validate the incoming request
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header row if your CSV has one
        $header = fgetcsv($handle); 

        $processedData = [];

        // 2. Loop through the CSV rows
        while (($row = fgetcsv($handle)) !== FALSE) {
            // Adjust index [0] based on which column contains the feedback text
            $feedbackText = $row[0]; 

            // 3. Call your AI Sentiment API (BERT/RoBERTa)
            // Replace the URL with your actual Colab or Local API endpoint
            try {
                $response = Http::post('https://tetragonal-tressie-unplundered.ngrok-free.dev/predict', [
                    'text' => $feedbackText
                ]);

                $sentiment = $response->json('sentiment'); // e.g., 'positive', 'negative'
                $score = $response->json('confidence');

                // 4. Save to Database
                Feedback::create([
                    'content' => $feedbackText,
                    'sentiment' => $sentiment,
                    'confidence_score' => $score,
                    'office' => $row[1] ?? 'General', // Example: second column is the office
                ]);

            } catch (\Exception $e) {
                Log::error("AI Model Error: " . $e->getMessage());
                // Fallback or skip if API fails
            }
        }

        fclose($handle);

        return back()->with('message', 'Dataset processed and saved successfully!');
    }
}