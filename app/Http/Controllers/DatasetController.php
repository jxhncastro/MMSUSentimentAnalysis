<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessSentimentDataset;
use App\Models\AnalysisBatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; 

class DatasetController extends Controller
{
    public function upload(Request $request)
    {
        Log::info("🔴 STEP 1: Upload Started");

        // 1. Validate
        $request->validate([
            'file' => 'required|file|max:100000'
        ]);

        // 2. DEFINE THE PATH
        // We force a simple name 'data.csv' to avoid weird character issues
        $folder = 'temp_datasets';
        $filename = 'data.csv'; 

        // 3. SAVE THE FILE (Overwrite if exists)
        // storeAs returns the relative path: "temp_datasets/data.csv"
        $path = $request->file('file')->storeAs($folder, $filename, 'local'); 
        
        // 4. GET THE REAL ABSOLUTE PATH
        // This asks Laravel: "Where on the hard drive is this file?"
        $fullPath = Storage::disk('local')->path($path);

        Log::info("🟢 STEP 2: File supposedly saved at: " . $fullPath);

        // 5. CRITICAL CHECK: Does it actually exist?
        if (!file_exists($fullPath)) {
            Log::error("❌ ERROR: File missing! Check permissions for folder: storage/app/" . $folder);
            return redirect()->back()->with('error', 'Server Error: File could not be saved to disk.');
        }

        // 6. Count Rows
        $lineCount = 0;
        if (($handle = fopen($fullPath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $lineCount++;
            }
            fclose($handle);
        }
        $totalRows = max(0, $lineCount - 1);

        // 7. Create Batch
        $batch = AnalysisBatch::create([
            'filename' => $request->file('file')->getClientOriginalName(), // Keep original name for display
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'status' => 'processing',
        ]);

        // 8. Dispatch Job
        Log::info("🟠 STEP 3: Dispatching Job...");
        ProcessSentimentDataset::dispatch($fullPath, $batch->id);
        
        return redirect()->back()->with([
            'success' => "File uploaded successfully! Processing $totalRows rows...",
            'batch_id' => $batch->id
        ]);
    }

    public function getStatus()
    {
        return response()->json(AnalysisBatch::latest()->first());
    }
}