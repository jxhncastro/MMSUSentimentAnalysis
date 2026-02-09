<?php
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

Route::post('/ai/analyze', function (Request $request) {
    // 1. Get the URL from .env
    $url = env('AI_MODEL_URL');

    // 2. Forward the request to Google Colab
    $response = Http::withHeaders([
        'ngrok-skip-browser-warning' => 'true', // Essential for free Ngrok accounts
    ])->post($url . '/predict', [
        'text' => $request->text,
        'aspect' => $request->aspect,
    ]);

    // 3. Return the AI's response back to Vue
    return $response->json();
});