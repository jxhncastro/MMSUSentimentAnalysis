<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
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

    public function index()
    {
        // 1. GLOBAL STATS
        $stats = [
            'total'    => Feedback::count(),
            'positive' => Feedback::where('sentiment', 'Positive')->count(),
            'negative' => Feedback::where('sentiment', 'Negative')->count(),
            'neutral'  => Feedback::where('sentiment', 'Neutral')->count(),
        ];

        // 2. EXCELLENCE AWARDEES (Top Offices by Positive Count)
        // We exclude survey ratings here so the actual Office Names can appear.
        $topPerformers = Feedback::select('operating_unit')
            ->whereNotIn(DB::raw('LOWER(TRIM(operating_unit))'), $this->excludedUnits)
            ->selectRaw("COUNT(CASE WHEN sentiment = 'Positive' THEN 1 END) as positive_count")
            ->groupBy('operating_unit')
            ->orderByDesc('positive_count')
            ->limit(3)
            ->get();

        $formattedTop = $topPerformers->map(function ($item, $index) {
            return [
                'rank'  => $index + 1,
                'unit'  => strtoupper($item->operating_unit),
                'score' => $item->positive_count . ' Positive Reviews',
                'color' => match($index) {
                    0 => 'bg-yellow-400',
                    1 => 'bg-gray-300',
                    2 => 'bg-orange-400',
                    default => 'bg-gray-100'
                }
            ];
        })->values();

        // 3. ACTION REQUIRED (Top Offices by Negative Count)
        $needsImprovement = Feedback::select('operating_unit')
            ->whereNotIn(DB::raw('LOWER(TRIM(operating_unit))'), $this->excludedUnits)
            ->selectRaw("COUNT(CASE WHEN sentiment = 'Negative' THEN 1 END) as negative_count")
            ->groupBy('operating_unit')
            ->orderByDesc('negative_count')
            ->limit(3)
            ->get();

        $formattedNeeds = $needsImprovement->map(function ($item, $index) {
            return [
                'rank'  => $index + 1,
                'unit'  => strtoupper($item->operating_unit),
                'issue' => 'Highest Complaint Volume', 
                'score' => $item->negative_count . ' Negatives'
            ];
        })->values();

        // 4. RECENT FEEDBACK
        $recentFeedback = Feedback::latest()
            ->take(5)
            ->get()
            ->map(function ($row) {
                return [
                    'unit'      => strtoupper($row->operating_unit ?? 'General'),
                    'text'      => str()->limit($row->feedback_text ?? '', 60), 
                    'sentiment' => ucfirst($row->sentiment ?? 'Neutral'),
                    'conf'      => ($row->confidence ?? 0) . '%',
                    'color'     => match($row->sentiment) {
                        'Positive' => 'text-green-600 bg-green-50',
                        'Negative' => 'text-red-600 bg-red-50',
                        default    => 'text-yellow-600 bg-yellow-50',
                    }
                ];
            });

        return Inertia::render('Dashboard', [
            'stats'            => $stats,
            'topPerformers'    => $formattedTop,
            'needsImprovement' => $formattedNeeds,
            'recentFeedback'   => $recentFeedback
        ]);
    }

    public function allFeedback()
    {
        $feedback = Feedback::orderBy('created_at', 'desc')->paginate(15);
        return Inertia::render('FeedbackList', ['feedback' => $feedback]);
    }
}