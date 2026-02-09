<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessSentimentDataset;
use App\Models\AnalysisBatch;
use Illuminate\Support\Facades\Storage;

class DatasetController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Validate the file (Increased max size for large datasets)
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:30720' // 30MB limit
        ]);

        // 2. Store the file temporarily
        $fileName = time() . '_' . $request->file('file')->getClientOriginalName();
        $path = $request->file('file')->storeAs('temp_datasets', $fileName);
        $fullPath = storage_path('app/' . $path);

        // 3. Count Total Rows (Excluding header)
        // This is necessary for the progress bar percentage
        $lineCount = 0;
        if (($handle = fopen($fullPath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $lineCount++;
            }
            fclose($handle);
        }
        $totalRows = max(0, $lineCount - 1); // Subtract 1 for the header row

        // 4. Create a Batch Tracking Record
        // This is what the Vue frontend polls via /api/analysis-status
        $batch = AnalysisBatch::create([
            'filename' => $request->file('file')->getClientOriginalName(),
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'status' => 'processing',
        ]);

        // 5. Dispatch the Background Job with the Batch ID
        ProcessSentimentDataset::dispatch($fullPath, $batch->id);

        // 6. Return success immediately
        return redirect()->back()->with([
            'success' => "File uploaded! $totalRows rows are being processed.",
            'batch_id' => $batch->id
        ]);
    }

    /**
     * API for Vue polling
     */
    public function getStatus()
    {
        // Get the latest active batch
        $batch = AnalysisBatch::where('status', 'processing')
                    ->orWhere('updated_at', '>', now()->subMinutes(5))
                    ->latest()
                    ->first();

        return response()->json($batch);
    }
}