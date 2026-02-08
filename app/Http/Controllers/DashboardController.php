<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Get Totals for Top Cards
        $stats = [
            'total'    => Feedback::count(),
            'positive' => Feedback::where('sentiment', 'Positive')->count(),
            'negative' => Feedback::where('sentiment', 'Negative')->count(),
            'neutral'  => Feedback::where('sentiment', 'Neutral')->count(),
        ];

        // 2. Get Top 3 Performing Units (Highest Positive Count)
        $topPerformers = Feedback::where('sentiment', 'Positive')
            ->select('operating_unit', DB::raw('count(*) as total'))
            ->groupBy('operating_unit')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(function($item, $index) {
                return [
                    'rank' => $index + 1,
                    'unit' => $item->operating_unit,
                    'score' => $item->total . ' Positives',
                    'color' => $index == 0 ? 'bg-yellow-400' : ($index == 1 ? 'bg-gray-300' : 'bg-orange-400')
                ];
            });

        // 3. Get Top 3 Needs Improvement (Highest Negative Count)
        $needsImprovement = Feedback::where('sentiment', 'Negative')
            ->select('operating_unit', DB::raw('count(*) as total'))
            ->groupBy('operating_unit')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(function($item, $index) {
                return [
                    'rank' => $index + 1,
                    'unit' => $item->operating_unit,
                    'score' => $item->total . ' Negatives',
                ];
            });

        // 4. Get Recent Feedback
        $recentFeedback = Feedback::latest()->limit(5)->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'topPerformers' => $topPerformers,
            'needsImprovement' => $needsImprovement,
            'recentFeedback' => $recentFeedback
        ]);
    }
}