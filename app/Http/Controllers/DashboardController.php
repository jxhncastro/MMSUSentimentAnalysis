<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\AnalysisBatch;
use App\Jobs\ProcessSentimentDataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // These match the logic in findUnitColumn() within your Job
    protected $excludedUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'n/a', 'na', 'none', 
        'general service', 'unknown', 'select office'
    ];

    public function index(Request $request)
    {
        // 0. FETCH AVAILABLE YEARS
        $availableYears = Feedback::select('year')
            ->whereNotNull('year')
            ->where('year', '!=', '')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $selectedYear = ($request->year && $request->year !== 'All Years') ? $request->year : null;

        // 1. GLOBAL STATS (Filtered by Year)
        $latestBatch = AnalysisBatch::latest()->first();
        
        $statsQuery = Feedback::query();
        if ($selectedYear) {
            $statsQuery->where('year', $selectedYear);
        }

        $stats = [
            // General metrics for the UI display
            'total'    => (clone $statsQuery)->count(),               
            'positive' => (clone $statsQuery)->where('sentiment', 'Positive')->count(),
            'negative' => (clone $statsQuery)->where('sentiment', 'Negative')->count(),
            'neutral'  => (clone $statsQuery)->where('sentiment', 'Neutral')->count(),
            
            // Session-specific stats from the latest AnalysisBatch
            'total_csv_rows'     => $latestBatch->total_rows ?? 0, 
            'processed_rows'     => $latestBatch->processed_rows ?? 0,
            'blank_count'        => $latestBatch->blank_count ?? 0,
            'na_count'           => $latestBatch->na_count ?? 0,
            'special_char_count' => $latestBatch->special_char_count ?? 0,
            'valid_count'        => $latestBatch->valid_count ?? 0,
            'batch_status'       => $latestBatch->status ?? 'idle',
        ];

        // 2. EXCELLENCE AWARDEES (Ranked by Positive Sentiment)
        $topPerformersQuery = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits);
        
        if ($selectedYear) {
            $topPerformersQuery->where('year', $selectedYear);
        }

        $topPerformers = $topPerformersQuery->selectRaw("COUNT(CASE WHEN sentiment = 'Positive' THEN 1 END) as positive_count")
            ->groupBy('office')
            ->orderByDesc('positive_count')
            ->limit(10) // Usually 10 is better for UI cards
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

        // 3. ACTION REQUIRED (Ranked by Negative Sentiment)
        $needsImprovementQuery = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits);

        if ($selectedYear) {
            $needsImprovementQuery->where('year', $selectedYear);
        }

        $needsImprovement = $needsImprovementQuery->selectRaw("COUNT(CASE WHEN sentiment = 'Negative' THEN 1 END) as negative_count")
            ->groupBy('office')
            ->orderByDesc('negative_count')
            ->limit(10)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'rank'  => $index + 1,
                    'unit'  => strtoupper($item->office),
                    'score' => $item->negative_count . ' Negatives'
                ];
            });

        // 4. FEEDBACK LIST
        $query = Feedback::query();

        if ($selectedYear) { $query->where('year', $selectedYear); }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('comment', 'like', '%'.$request->search.'%')
                  ->orWhere('office', 'like', '%'.$request->search.'%')
                  ->orWhere('topic', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->unit) { $query->where('office', $request->unit); }

        if ($request->sort_sentiment) {
            $query->orderBy('sentiment', $request->sort_sentiment);
        } else {
            $query->orderBy('year', 'desc')->orderBy('created_at', 'desc');
        }

        $feedback = $query->paginate(10)
            ->withQueryString()
            ->through(fn ($item) => [
                'id'               => $item->id,
                'year'             => $item->year,
                'operating_unit'   => strtoupper($item->office), 
                'feedback_text'    => $item->comment, 
                'services_availed' => $item->services_availed, 
                'topic'            => $item->topic ?? 'General',
                'sentiment'        => $item->sentiment,
                'confidence'       => $item->confidence,
                'method'           => $item->method, // Shows if AI or Fallback was used
            ]);

        return Inertia::render('Dashboard', [
            'stats'            => $stats,
            'topPerformers'    => $topPerformers,
            'needsImprovement' => $needsImprovement,
            'feedback'         => $feedback,
            'availableYears'   => $availableYears,
            'latestBatch'      => $latestBatch,
            'filters'          => $request->only(['search', 'unit', 'sort_sentiment', 'year'])
        ]);
    }

    public function addCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->store('temp');

        $batch = AnalysisBatch::create([
            'filename' => $request->file('csv_file')->getClientOriginalName(),
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
        ]);

        // Hand off to the Job
        ProcessSentimentDataset::dispatch(storage_path('app/' . $path), $batch->id);

        return back()->with('success', 'Processing dataset... Please wait.');
    }

    public function clearData()
    {
        Feedback::truncate();
        AnalysisBatch::truncate();
        return back();
    }
}