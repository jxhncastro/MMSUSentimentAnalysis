<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\DatasetController; // <--- IMPORTANT: Import your controller

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| URL Prefix: /api
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * 1. AI PROXY ENDPOINT (For single text analysis)
 */
Route::post('/ai/analyze', function (Request $request) {
    $request->validate([
        'text' => 'required|string',
        'aspect' => 'nullable|string'
    ]);

    $aiUrl = env('AI_MODEL_URL');
    if (!$aiUrl) {
        return response()->json(['error' => 'AI_MODEL_URL is not set in .env'], 500);
    }

    try {
        $response = Http::withHeaders([
            'ngrok-skip-browser-warning' => 'true',
            'Content-Type' => 'application/json'
        ])->timeout(5)->post($aiUrl . '/predict', [
            'text' => $request->text,
            'aspect' => $request->aspect ?? 'General',
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'AI Error', 'details' => $response->body()], $response->status());
        }

        return $response->json();

    } catch (\Exception $e) {
        return response()->json(['error' => 'Connection Error', 'message' => $e->getMessage()], 500);
    }
});

/**
 * 2. PROGRESS BAR STATUS ENDPOINT (The Missing Link!)
 * This allows the frontend to ask "How many rows are done?"
 */
Route::get('/analysis-status', [DatasetController::class, 'getStatus']);