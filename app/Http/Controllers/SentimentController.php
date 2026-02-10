<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SentimentController extends Controller
{
    // --- 1. CLEAR DATA FUNCTION ---
    public function clearData()
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            DB::table('feedback')->truncate();
            DB::table('sentiment_results')->truncate();
            DB::table('analysis_batches')->truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return redirect()->back()->with('success', '✅ All data has been wiped successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error clearing data: ' . $e->getMessage());
        }
    }

    // --- 2. MAIN ANALYSIS FUNCTION ---
    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'aspect' => 'required|string',
        ]);

        $text = strtolower(trim($request->text));

        // --- STEP 1: HYBRID OVERRIDE LOGIC ---
        
        // Strict Neutral List: Only for actual "Empty" responses
        $neutralKeywords = [
            'none', 'nothing', 'n/a', 'nil', 'wala', 'awan', 'no suggestion', 
            'no comment', 'na', 'n a', 'none.', 'wala po', 'awan po'
        ];
        
        $negativeKeywords = [
            'rude', 'bad', 'slow', 'nabuntog', 'madi', 'worst', 'poor', 
            'bastos', 'attitude', 'terrible', 'disappointing', 'bagal', 'mabagal', 'pangit'
        ]; 
        
        $positiveSavers = [
            'not bad', 'not rude', 'good', 'great', 'best', 'excellent', 
            'pintas', 'sayaat', 'napintas', 'nice', 'fast', 'mabilis', 'satisfied'
        ];

        // CHECK 1: Positive Savers
        $hasPositiveSaver = false;
        foreach ($positiveSavers as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text)) {
                $hasPositiveSaver = true;
                break;
            }
        }

        if (!$hasPositiveSaver) {
            // CHECK 2: NEGATIVE GUARD (Manual override for obvious bad words)
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

            // CHECK 3: NEUTRAL GUARD (Only for very short text)
            $isNeutral = false;
            foreach ($neutralKeywords as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text)) {
                    $isNeutral = true;
                    break;
                }
            }

            // Reduced to 25 chars to prevent capturing actual feedback
            if ($isNeutral && strlen($text) < 25) { 
                return response()->json([
                    'sentiment' => 'Neutral',
                    'confidence' => 100.0,
                    'method' => 'Heuristic Override',
                    'aspect_used' => $request->aspect
                ]);
            }
        }

        // --- STEP 2: CALL GOOGLE COLAB AI ---
        try {
            $url = rtrim(env('AI_MODEL_URL'), '/');

            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => 'true',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url . '/predict', [
                'text' => $request->text,
                'aspect' => $request->aspect,
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                
                // ✅ REMOVED THE 40% THRESHOLD:
                // We trust the Python "Decisive" logic now.
                // This prevents Laravel from turning a "lean-positive" result into Neutral.
                
                return response()->json($aiData);
            }

            Log::error("AI API Error: " . $response->body());
            return response()->json(['error' => 'AI Server error'], 500);

        } catch (\Exception $e) {
            Log::error("Connection Error: " . $e->getMessage());
            return response()->json(['error' => 'Could not connect to AI'], 503);
        }
    }
}