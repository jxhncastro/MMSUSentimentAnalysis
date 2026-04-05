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
    protected $excludedUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'n/a', 'na', 'none', 
        'general service', 'unknown', 'select office'
    ];

    public function index(Request $request)
    {
        // 0. FETCH AVAILABLE YEARS FOR DROPDOWN
        $availableYears = Feedback::select('year')
            ->whereNotNull('year')
            ->where('year', '!=', '')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Normalize the year filter: Treat null, empty, or "All Years" as a request for all data
        $selectedYear = ($request->year && $request->year !== 'All Years') ? $request->year : null;

        // 1. GLOBAL STATS (Filtered by Year)
        $latestBatch = AnalysisBatch::latest()->first();
        
        $statsQuery = Feedback::query();
        if ($selectedYear) {
            $statsQuery->where('year', $selectedYear);
        }

        $stats = [
            'total_csv_rows' => $latestBatch->total_rows ?? 0, 
            'total'          => (clone $statsQuery)->count(),               
            'positive'       => (clone $statsQuery)->where('sentiment', 'Positive')->count(),
            'negative'       => (clone $statsQuery)->where('sentiment', 'Negative')->count(),
            'neutral'        => (clone $statsQuery)->where('sentiment', 'Neutral')->count(),
            
            // Background process stats (specific to the latest upload session)
            'blank_count'        => $latestBatch->blank_count ?? 0,
            'na_count'           => $latestBatch->na_count ?? 0,
            'special_char_count' => $latestBatch->special_char_count ?? 0,
            'valid_count'        => $latestBatch->valid_count ?? 0,
        ];

        // 2. EXCELLENCE AWARDEES (Filtered by Year)
        $topPerformersQuery = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits);
        
        if ($selectedYear) {
            $topPerformersQuery->where('year', $selectedYear);
        }

        $topPerformers = $topPerformersQuery->selectRaw("COUNT(CASE WHEN sentiment = 'Positive' THEN 1 END) as positive_count")
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

        // 3. ACTION REQUIRED (Filtered by Year)
        $needsImprovementQuery = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits);

        if ($selectedYear) {
            $needsImprovementQuery->where('year', $selectedYear);
        }

        $needsImprovement = $needsImprovementQuery->selectRaw("COUNT(CASE WHEN sentiment = 'Negative' THEN 1 END) as negative_count")
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

        // 4. FEEDBACK LIST (Filtered by Year, Search, and Unit)
        $query = Feedback::query();

        if ($selectedYear) {
            $query->where('year', $selectedYear);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('comment', 'like', '%'.$request->search.'%')
                  ->orWhere('office', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->unit) {
            $query->where('office', $request->unit);
        }

        // Apply Sorting: Priority to Year, then Sentiment or Date
        if ($request->sort_sentiment) {
            $query->orderBy('sentiment', $request->sort_sentiment);
        } else {
            // Priority sorting to keep temporal order
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

        // Note: Ensure your Queue Worker is running: php artisan queue:work
        ProcessSentimentDataset::dispatch(storage_path('app/' . $path), $batch->id);

        return back()->with('success', 'File uploaded! Processing in the background...');
    }

    public function clearData()
    {
        Feedback::truncate();
        AnalysisBatch::truncate();
        return back();
    }
}