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

        // 🔴 UPDATE THIS URL whenever you restart Google Colab
        $pythonUrl = 'https://tetragonal-tressie-unplundered.ngrok-free.dev/predict';

        try {
            // We add 'ngrok-skip-browser-warning' header to bypass the Ngrok landing page
            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => '69420',
            ])
            ->timeout(60) // AI models can be slow, so we wait up to 60 seconds
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
            // This captures if the URL is wrong or Ngrok is offline
            return response()->json([
                'error' => 'Connection Failed.',
                'message' => 'Ensure Google Colab is running and the URL is correct.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}