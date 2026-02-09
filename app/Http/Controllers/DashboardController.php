<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. STATS (Safe)
        $stats = [
            'total'    => Feedback::count(),
            'positive' => Feedback::where('sentiment', 'Positive')->count(),
            'negative' => Feedback::where('sentiment', 'Negative')->count(),
            'neutral'  => Feedback::where('sentiment', 'Neutral')->count(),
        ];

        // 2. TOP PERFORMING (Safe for empty DB)
        $topPerformers = Feedback::select('operating_unit')
            ->selectRaw('count(*) as total')
            ->selectRaw("AVG(CASE WHEN sentiment = 'Positive' THEN 100 ELSE 0 END) as satisfaction_score")
            ->groupBy('operating_unit')
            ->having('total', '>', 5)
            ->orderByDesc('satisfaction_score')
            ->limit(3)
            ->get(); // If empty, this returns an empty Collection, which is safe

        $formattedTop = $topPerformers->map(function ($item, $index) {
            return [
                'rank'  => $index + 1,
                'unit'  => ucfirst($item->operating_unit),
                'score' => round($item->satisfaction_score) . '%',
                'color' => match($index) {
                    0 => 'bg-yellow-400',
                    1 => 'bg-gray-300',
                    2 => 'bg-orange-400',
                    default => 'bg-gray-100'
                }
            ];
        })->values(); // Ensure it's a clean array

        // 3. NEEDS IMPROVEMENT (Safe for empty DB)
        $needsImprovement = Feedback::select('operating_unit')
            ->selectRaw('count(*) as total')
            ->selectRaw("AVG(CASE WHEN sentiment = 'Negative' THEN 100 ELSE 0 END) as negative_score")
            ->groupBy('operating_unit')
            ->having('total', '>', 5)
            ->orderByDesc('negative_score')
            ->limit(3)
            ->get();

        $formattedNeeds = $needsImprovement->map(function ($item, $index) {
            $posCount = Feedback::where('operating_unit', $item->operating_unit)
                                ->where('sentiment', 'Positive')
                                ->count();
            // Avoid Division by Zero
            $posScore = $item->total > 0 ? round(($posCount / $item->total) * 100) : 0;
            
            return [
                'rank'  => $index + 1,
                'unit'  => ucfirst($item->operating_unit),
                'issue' => 'High Negative Sentiment', 
                'score' => $posScore . '%'
            ];
        })->values();

        // 4. RECENT FEEDBACK (Safe)
        $recentFeedback = Feedback::latest()
            ->take(5)
            ->get()
            ->map(function ($row) {
                return [
                    'unit'      => ucfirst($row->operating_unit ?? 'Unknown'),
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
            'topPerformers'    => $formattedTop,  // Use the mapped variable
            'needsImprovement' => $formattedNeeds, // Use the mapped variable
            'recentFeedback'   => $recentFeedback
        ]);
    }
}