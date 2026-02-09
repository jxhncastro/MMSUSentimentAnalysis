<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentController extends Controller
{
    public function analyze(Request $request)
    {
        // 1. Validation
        $request->validate([
            'text' => 'required|string',
            'aspect' => 'required|string',
        ]);

        $text = strtolower(trim($request->text));
        
        // --- STEP 2: HYBRID OVERRIDE LOGIC (Lexicon Guards) ---
        
        // A. Neutral Keywords (Handles "None", "Wala", "Awan")
        $neutralKeywords = ['none', 'nothing', 'n/a', 'nil', 'wala', 'awan', 'no suggestion', 
        'ok', 'okay', 'fine', 'satisfied', 'neutral', 'n/a', 'na', 'n a', 'no comment', 
        'no suggestions', 'none.', 'none po', 'nons', 'n/q', 'nne', 'notjing', 'nonia', 'nonw', 
        'n/aa', 'n aa', 'wala po', 'meron', 'wala naman', 'awan po', 'awanen', 'noise', 'nang', 
        'nangruna', 'nangrunaan', 'nangrunaen', 'nagruna', 'nagrunaen'];
        
        // B. High-Priority Negative Keywords (Triggers automatic Negative)
        // Added 'rude' and common Ilocano complaints
        $negativeKeywords = ['rude', 'bad', 'slow', 'nabuntog', 'madi', 'worst', 'poor', 'bastos', 'attitude', 'terrible', 'disappointing']; 
        
        // C. Positive "Savers" (Prevents overriding things like "not bad" or "not rude")
        $positiveSavers = ['good', 'great', 'best', 'excellent', 'pintas', 'sayaat', 'napintas', 'nice'];

        // Check for Positive Savers first
        $hasPositiveSaver = false;
        foreach ($positiveSavers as $word) {
            if (str_contains($text, $word)) {
                $hasPositiveSaver = true;
                break;
            }
        }

        if (!$hasPositiveSaver) {
            // 1. NEGATIVE GUARD: If it contains words like 'rude', it's Negative regardless of length
            foreach ($negativeKeywords as $neg) {
                if (str_contains($text, $neg)) {
                    return response()->json([
                        'sentiment' => 'Negative',
                        'confidence' => 99.9,
                        'method' => 'Lexicon_Guard_Override',
                        'aspect_used' => $request->aspect
                    ]);
                }
            }

            // 2. NEUTRAL GUARD: For short "N/A" style responses
            $isNeutral = false;
            foreach ($neutralKeywords as $word) {
                if (str_contains($text, $word)) {
                    $isNeutral = true;
                    break;
                }
            }

            if ($isNeutral && strlen($text) < 25) {
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
            $url = config('services.ai.url') ?? env('AI_MODEL_URL');
            
            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => 'true',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url . '/predict', [
                'text' => $request->text,
                'aspect' => $request->aspect,
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                
                // FINAL ACCURACY CHECK: If AI is very unsure, label as Neutral
                if (isset($aiData['confidence']) && $aiData['confidence'] < 60.0) {
                    $aiData['sentiment'] = 'Neutral';
                    $aiData['method'] = ($aiData['method'] ?? 'Transformer') . ' (Low Confidence Adjustment)';
                }
                
                return $aiData;
            }

            Log::error("AI API Error: " . $response->body());
            return response()->json(['error' => 'AI Server returned an error.'], 500);

        } catch (\Exception $e) {
            Log::error("Connection Error: " . $e->getMessage());
            return response()->json(['error' => 'Could not connect to Colab. Check Ngrok URL.'], 503);
        }
    }
}