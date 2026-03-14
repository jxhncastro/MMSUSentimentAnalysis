<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\AnalysisBatch; // Added this
use App\Jobs\ProcessSentimentDataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $excludedUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'n/a', 'na', 'none', 
        'general service', 'unknown', 'select office'
    ];

    public function index(Request $request)
    {
        // 1. GLOBAL STATS
        // Get the most recent batch to show the Raw CSV total
        $latestBatch = AnalysisBatch::latest()->first();

        $stats = [
            'total_csv_rows' => $latestBatch->total_rows ?? 0, // THE RAW CSV TOTAL
            'total'          => Feedback::count(),              // THE PROCESSED TOTAL
            'positive'       => Feedback::where('sentiment', 'Positive')->count(),
            'negative'       => Feedback::where('sentiment', 'Negative')->count(),
            'neutral'        => Feedback::where('sentiment', 'Neutral')->count(),
        ];

        // 2. EXCELLENCE AWARDEES
        $topPerformers = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
            ->selectRaw("COUNT(CASE WHEN sentiment = 'Positive' THEN 1 END) as positive_count")
            ->groupBy('office')
            ->orderByDesc('positive_count')
            ->limit(40)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'rank'  => $index + 1,
                    'unit'  => strtoupper($item->office),
                    'score' => $item->positive_count . ' Positive Reviews',
                    'color' => match($index) {
                        0 => 'bg-yellow-400',
                        1 => 'bg-gray-300',
                        2 => 'bg-orange-400',
                        default => 'bg-gray-100'
                    }
                ];
            });

        // 3. ACTION REQUIRED
        $needsImprovement = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
            ->selectRaw("COUNT(CASE WHEN sentiment = 'Negative' THEN 1 END) as negative_count")
            ->groupBy('office')
            ->orderByDesc('negative_count')
            ->limit(40)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'rank'  => $index + 1,
                    'unit'  => strtoupper($item->office),
                    'issue' => 'Highest Complaint Volume', 
                    'score' => $item->negative_count . ' Negatives'
                ];
            });

        // 4. FEEDBACK LIST
        $query = Feedback::query();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('comment', 'like', '%'.$request->search.'%')
                  ->orWhere('office', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->unit) {
            $query->where('office', $request->unit);
        }

        if ($request->sort_sentiment) {
            $query->orderBy('sentiment', $request->sort_sentiment);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $feedback = $query->paginate(5)
            ->withQueryString()
            ->through(fn ($item) => [
                'id'               => $item->id,
                'operating_unit'   => strtoupper($item->office), 
                'feedback_text'    => $item->comment, 
                'services_availed' => $item->services_availed, 
                'topic'            => $item->topic ?? 'General',
                'sentiment'        => $item->sentiment,
                'confidence'       => $item->confidence,
            ]);

        return Inertia::render('Dashboard', [
            'stats'            => $stats,
            'topPerformers'    => $topPerformers,
            'needsImprovement' => $needsImprovement,
            'feedback'         => $feedback,
            'filters'          => $request->only(['search', 'unit', 'sort_sentiment']) 
        ]);
    }

    public function addCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->store('temp');

        // Create the Batch record first so the job can update it
        $batch = AnalysisBatch::create([
            'filename' => $request->file('csv_file')->getClientOriginalName(),
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
        ]);

        // Dispatch with the required batchId
        ProcessSentimentDataset::dispatch(storage_path('app/' . $path), $batch->id);

        return back()->with('success', 'File uploaded! Processing in the background...');
    }

    public function clearData()
    {
        Feedback::truncate();
        AnalysisBatch::truncate(); // Also clear batches when clearing data
        return back();
    }
}