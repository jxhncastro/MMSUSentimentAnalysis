<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SentimentController extends Controller
{
    // --- 1. CLEAR DATA FUNCTION (From previous step) ---
    public function clearData()
        {
            try {
                // 1. Disable Foreign Key Checks (Prevents crashing if tables are linked)
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // 2. Truncate the CORRECT tables (Based on your migration log)
                // We use 'truncate' to wipe them clean and reset IDs to 1.
                DB::table('feedback')->truncate();
                DB::table('sentiment_results')->truncate();
                DB::table('analysis_batches')->truncate();

                // 3. Re-enable Foreign Key Checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                return redirect()->back()->with('success', '✅ All data has been wiped successfully!');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error clearing data: ' . $e->getMessage());
            }
        }

    // --- 2. MAIN ANALYSIS FUNCTION ---
    public function analyze(Request $request)
    {
        // A. Validation
        $request->validate([
            'text' => 'required|string',
            'aspect' => 'required|string',
        ]);

        $text = strtolower(trim($request->text));

        // --- STEP 2: HYBRID OVERRIDE LOGIC (Lexicon Guards) ---
        
        // A. Neutral Keywords (Handles "None", "Wala", "Awan")
        $neutralKeywords = [
            'none', 'nothing', 'n/a', 'nil', 'wala', 'awan', 'no suggestion', 
            'ok', 'okay', 'fine', 'satisfied', 'neutral', 'na', 'n a', 'no comment', 
            'no suggestions', 'none.', 'none po', 'nons', 'n/q', 'nne', 'notjing', 'nonia', 'nonw', 
            'n/aa', 'n aa', 'wala po', 'meron', 'wala naman', 'awan po', 'awanen', 'noise', 'nang', 
            'nangruna', 'nangrunaan', 'nangrunaen', 'nagruna', 'nagrunaen'
        ];
        
        // B. High-Priority Negative Keywords (Triggers automatic Negative)
        $negativeKeywords = [
            'rude', 'bad', 'slow', 'nabuntog', 'madi', 'worst', 'poor', 
            'bastos', 'attitude', 'terrible', 'disappointing', 'bagal', 'mabagal'
        ]; 
        
        // C. Positive "Savers" (Prevents overriding things like "not bad")
        $positiveSavers = [
            'not bad', 'not rude', 'good', 'great', 'best', 'excellent', 
            'pintas', 'sayaat', 'napintas', 'nice', 'fast', 'mabilis'
        ];

        // CHECK 1: Positive Savers (If found, SKIP Negative Guard)
        $hasPositiveSaver = false;
        foreach ($positiveSavers as $word) {
            // We use regex \b to match WHOLE WORDS only (avoids "bad" matching "badminton")
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text)) {
                $hasPositiveSaver = true;
                break;
            }
        }

        if (!$hasPositiveSaver) {
            // CHECK 2: NEGATIVE GUARD
            foreach ($negativeKeywords as $neg) {
                if (preg_match('/\b' . preg_quote($neg, '/') . '\b/', $text)) {
                    return response()->json([
                        'sentiment' => 'Negative',
                        'confidence' => 99.9,
                        'method' => 'Lexicon_Guard_Override',
                        'aspect_used' => $request->aspect
                    ]);
                }
            }

            // CHECK 3: NEUTRAL GUARD (Only for short texts < 50 chars)
            $isNeutral = false;
            foreach ($neutralKeywords as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text)) {
                    $isNeutral = true;
                    break;
                }
            }

            if ($isNeutral && strlen($text) < 50) {
                return response()->json([
                    'sentiment' => 'Neutral',
                    'confidence' => 100.0,
                    'method' => 'Heuristic Override',
                    'aspect_used' => $request->aspect
                ]);
            }
        }

        // --- STEP 3: CALL GOOGLE COLAB AI ---
        try {
            // Get URL from .env (e.g., AI_MODEL_URL=https://xyz.ngrok-free.app)
            $url = env('AI_MODEL_URL');
            
            // If URL ends with a slash, remove it to prevent double slashes
            $url = rtrim($url, '/');

            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => 'true', // Important for Ngrok free tier
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url . '/predict', [
                'text' => $request->text,
                'aspect' => $request->aspect,
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                
                // FINAL ACCURACY CHECK: If AI is unsure (< 60%), default to Neutral
                if (isset($aiData['confidence']) && $aiData['confidence'] < 60.0) {
                    $aiData['sentiment'] = 'Neutral';
                    $aiData['method'] = ($aiData['method'] ?? 'Transformer') . ' (Low Confidence Adjustment)';
                }
                
                return response()->json($aiData);
            }

            // Log the error if AI fails
            Log::error("AI API Error: " . $response->body());
            return response()->json([
                'error' => 'AI Server returned an error.',
                'details' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error("Connection Error: " . $e->getMessage());
            return response()->json([
                'error' => 'Could not connect to AI Model.',
                'suggestion' => 'Check if the Ngrok URL in .env is correct and active.'
            ], 503);
        }
    }
}