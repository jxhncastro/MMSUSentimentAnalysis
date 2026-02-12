<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Jobs\ProcessSentimentDataset; // Ensure this job exists in App\Jobs
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Strict list of survey noise to exclude from Top Rankings.
     */
    protected $excludedUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'n/a', 'na', 'none', 
        'general service', 'unknown', 'select office'
    ];

    public function index(Request $request)
    {
        // 1. GLOBAL STATS
        $stats = [
            'total'    => Feedback::count(),
            'positive' => Feedback::where('sentiment', 'Positive')->count(),
            'negative' => Feedback::where('sentiment', 'Negative')->count(),
            'neutral'  => Feedback::where('sentiment', 'Neutral')->count(),
        ];

        // 2. EXCELLENCE AWARDEES (Top Offices by Positive Count)
        $topPerformers = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
            ->selectRaw("COUNT(CASE WHEN sentiment = 'Positive' THEN 1 END) as positive_count")
            ->groupBy('office')
            ->orderByDesc('positive_count')
            ->limit(3)
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

        // 3. ACTION REQUIRED (Top Offices by Negative Count)
        $needsImprovement = Feedback::select('office')
            ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
            ->selectRaw("COUNT(CASE WHEN sentiment = 'Negative' THEN 1 END) as negative_count")
            ->groupBy('office')
            ->orderByDesc('negative_count')
            ->limit(3)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'rank'  => $index + 1,
                    'unit'  => strtoupper($item->office),
                    'issue' => 'Highest Complaint Volume', 
                    'score' => $item->negative_count . ' Negatives'
                ];
            });

        // 4. FEEDBACK LIST (With Search & Operating Unit Filter)
        $query = Feedback::query();

        // Apply Search Filter
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('comment', 'like', '%'.$request->search.'%')
                  ->orWhere('office', 'like', '%'.$request->search.'%');
            });
        }

        // Apply Operating Unit Filter
        if ($request->unit) {
            $query->where('office', $request->unit);
        }

        $feedback = $query->orderBy('created_at', 'desc')
            ->paginate(5)
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
            'filters'          => $request->only(['search', 'unit']) 
        ]);
    }

    /**
     * Restore CSV Upload functionality using Queue Jobs
     */
    public function addCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Store the file temporarily in storage/app/temp
        $path = $request->file('csv_file')->store('temp');

        // Dispatch the background Job (triggers php artisan queue:work)
        ProcessSentimentDataset::dispatch(storage_path('app/' . $path));

        return back()->with('success', 'File uploaded! Processing in the background...');
    }

    /**
     * Restore Clear All Data functionality
     */
    public function clearData()
    {
        Feedback::truncate();
        return back();
    }
}