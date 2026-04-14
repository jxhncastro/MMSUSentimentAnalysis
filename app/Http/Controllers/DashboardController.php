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
        // ... (Keep your existing index logic exactly as it is) ...
        $availableYears = Feedback::select('year')->whereNotNull('year')->where('year', '!=', '')->distinct()->orderBy('year', 'desc')->pluck('year');
        $selectedYear = ($request->year && $request->year !== 'All Years') ? $request->year : null;

        $latestBatch = AnalysisBatch::latest()->first();
        $statsQuery = Feedback::query();
        if ($selectedYear) { $statsQuery->where('year', $selectedYear); }

        $stats = [
            'total'    => (clone $statsQuery)->count(),               
            'positive' => (clone $statsQuery)->where('sentiment', 'Positive')->count(),
            'negative' => (clone $statsQuery)->where('sentiment', 'Negative')->count(),
            'neutral'  => (clone $statsQuery)->where('sentiment', 'Neutral')->count(),
            'total_csv_rows'     => $latestBatch->total_rows ?? 0, 
            'processed_rows'     => $latestBatch->processed_rows ?? 0,
            'blank_count'        => $latestBatch->blank_count ?? 0,
            'na_count'           => $latestBatch->na_count ?? 0,
            'special_char_count' => $latestBatch->special_char_count ?? 0,
            'valid_count'        => $latestBatch->valid_count ?? 0,
            'batch_status'       => $latestBatch->status ?? 'idle',
        ];

        $topPerformersQuery = Feedback::select('office')->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits);
        if ($selectedYear) { $topPerformersQuery->where('year', $selectedYear); }
        $topPerformers = $topPerformersQuery->selectRaw("COUNT(CASE WHEN sentiment = 'Positive' THEN 1 END) as positive_count")
            ->groupBy('office')->orderByDesc('positive_count')->limit(10)->get()
            ->map(function ($item, $index) {
                return ['rank' => $index + 1, 'unit' => strtoupper($item->office), 'score' => $item->positive_count . ' Positive Reviews', 'color' => match($index) { 0 => 'bg-yellow-400', 1 => 'bg-gray-300', 2 => 'bg-orange-400', default => 'bg-gray-100' }];
            });

        $needsImprovementQuery = Feedback::select('office')->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits);
        if ($selectedYear) { $needsImprovementQuery->where('year', $selectedYear); }
        $needsImprovement = $needsImprovementQuery->selectRaw("COUNT(CASE WHEN sentiment = 'Negative' THEN 1 END) as negative_count")
            ->groupBy('office')->orderByDesc('negative_count')->limit(10)->get()
            ->map(function ($item, $index) {
                return ['rank' => $index + 1, 'unit' => strtoupper($item->office), 'score' => $item->negative_count . ' Negatives'];
            });

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

        // Logic for filtering by specific sentiment if chosen
        if ($request->sentiment_filter) {
            $query->where('sentiment', $request->sentiment_filter);
        }

        if ($request->sort_sentiment) {
            $query->orderBy('sentiment', $request->sort_sentiment);
        } else {
            $query->orderBy('year', 'desc')->orderBy('created_at', 'desc');
        }

        $feedback = $query->paginate(5)->withQueryString()->through(fn ($item) => [
            'id'               => $item->id,
            'year'             => $item->year,
            'operating_unit'   => strtoupper($item->office), 
            'feedback_text'    => $item->comment, 
            'services_availed' => $item->services_availed, 
            'topic'            => $item->topic ?? 'General',
            'sentiment'        => $item->sentiment,
            'confidence'       => $item->confidence,
            'method'           => $item->method,
        ]);

        return Inertia::render('Dashboard', [
            'stats'            => $stats,
            'topPerformers'    => $topPerformers,
            'needsImprovement' => $needsImprovement,
            'feedback'         => $feedback,
            'availableYears'   => $availableYears,
            'latestBatch'      => $latestBatch,
            'filters'          => $request->only(['search', 'unit', 'sort_sentiment', 'year', 'sentiment_filter'])
        ]);
    }

    /**
     * NEW EXPORT FEATURE
     */
public function export(Request $request)
{
    $query = Feedback::query();

    // Apply filters
    if ($request->year && $request->year !== 'All Years') { 
        $query->where('year', $request->year); 
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
    if ($request->sentiment) {
        $query->where('sentiment', $request->sentiment);
    }

    $records = $query->orderBy('created_at', 'desc')->get();
    $sentimentLabel = $request->sentiment ?? 'All';
    $fileName = "MMSU_Feedback_{$sentimentLabel}_" . now()->format('Ymd_His') . ".csv";

    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use ($records) {
        $file = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Row
        fputcsv($file, ['Year', 'Operating Unit', 'Services Availed', 'Feedback', 'Sentiment']);

        foreach ($records as $row) {
            // --- UPDATED CLEANING LOGIC ---
            // 1. Split by either a semicolon or a comma (where the survey noise usually starts)
            // 2. Take the very first part (the actual service name)
            $parts = preg_split('/[;,]/', $row->services_availed);
            $cleanService = $parts[0];

            // 3. Remove that weird "Â" character just in case it's in the first part
            $cleanService = str_replace('Â', '', $cleanService);

            // 4. Final trim for whitespace
            $cleanService = trim($cleanService);

            fputcsv($file, [
                $row->year,
                strtoupper($row->office),
                $cleanService, // Now strictly the service name
                $row->comment,
                $row->sentiment
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function addCsv(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
        $path = $request->file('csv_file')->store('temp');
        $batch = AnalysisBatch::create([
            'filename' => $request->file('csv_file')->getClientOriginalName(),
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
        ]);
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