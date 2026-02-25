<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'comment' => 'required|string',
            'aspect' => 'nullable|string'
        ]);

        // 🟢 Automatically pulls the local URL from your .env file
        $baseUrl = env('AI_MODEL_URL', 'http://127.0.0.1:5000');
        $pythonUrl = rtrim($baseUrl, '/') . '/predict';

        try {
            // No more ngrok headers needed! Just a clean, fast local request.
            $response = Http::timeout(60) // AI models can still take a few seconds
            ->post($pythonUrl, [
                'text' => $request->comment,
                'aspect' => $request->aspect ?? 'General'
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json([
                    'error' => 'AI Model reached, but returned an error.',
                    'details' => $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            // Updated to reflect your new local setup instead of Colab
            return response()->json([
                'error' => 'Connection Failed.',
                'message' => 'Ensure your local Python API (Uvicorn) is running on port 5000.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}