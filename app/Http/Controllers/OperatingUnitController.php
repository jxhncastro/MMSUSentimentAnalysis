<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperatingUnitController extends Controller
{
    protected $excludedUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'n/a', 'na', 'none', 
        'general service', 'unknown', 'select office'
    ];

    public function index(Request $request)
    {
        // 1. Get available years for the dropdown filter
        $availableYears = Feedback::whereNotNull('year')
            ->where('year', '!=', '')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // 2. Base Query
        $query = Feedback::query();

        // 3. Filter by Year (NEW)
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // 4. Filter by Operating Unit (Office)
        if ($request->filled('unit')) {
            $query->where(DB::raw('LOWER(TRIM(office))'), strtolower(trim($request->unit)));
        }

        // 5. Filter by Specific Service (using 'services_availed' column)
        if ($request->filled('service') && $request->service !== 'All Services') {
            $query->where('services_availed', 'LIKE', '%' . trim($request->service) . '%');
        }

        // --- CHART 1: OVERALL SATISFACTION (Donut) ---
        $rawSentiment = (clone $query)
            ->selectRaw('LOWER(TRIM(sentiment)) as sentiment_key, count(*) as count')
            ->groupBy('sentiment_key')
            ->pluck('count', 'sentiment_key')
            ->toArray();

        $overallSentiment = [
            'Positive' => $rawSentiment['positive'] ?? 0,
            'Negative' => $rawSentiment['negative'] ?? 0,
            'Neutral'  => $rawSentiment['neutral']  ?? 0,
        ];

        // --- CHART 2: SENTIMENT BY TOPIC (Stacked Bar) ---
        $sentimentByTopic = (clone $query)
            ->selectRaw('topic, LOWER(TRIM(sentiment)) as sentiment_key, count(*) as count')
            ->whereNotNull('topic')
            ->where('topic', '!=', 'General')
            ->groupBy('topic', 'sentiment_key')
            ->get()
            ->groupBy('topic')
            ->map(function ($group) {
                return [
                    'positive' => $group->where('sentiment_key', 'positive')->sum('count'),
                    'negative' => $group->where('sentiment_key', 'negative')->sum('count'),
                    'neutral'  => $group->where('sentiment_key', 'neutral')->sum('count'),
                ];
            })->toArray();

        // --- CHART 3 & 4: GLOBAL TOP LISTS ---
        $topNegative = [];
        $topPositive = [];
        
        if (!$request->filled('unit')) {
            // Apply year filter to global lists as well if a year is selected
            $globalQuery = Feedback::query();
            if ($request->filled('year')) {
                $globalQuery->where('year', $request->year);
            }

            $topNegative = (clone $globalQuery)
                ->whereRaw("LOWER(TRIM(sentiment)) = 'negative'")
                ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
                ->selectRaw('office, count(*) as count')
                ->groupBy('office')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            $topPositive = (clone $globalQuery)
                ->whereRaw("LOWER(TRIM(sentiment)) = 'positive'")
                ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
                ->selectRaw('office, count(*) as count')
                ->groupBy('office')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
        }

        // --- 5. RECENT FEEDBACK TABLE ---
        $recentFeedback = (clone $query)
            ->latest()
            ->take(10)
            ->get()
            ->map(function($item) {
                $item->sentiment = ucfirst(strtolower(trim($item->sentiment))); 
                return $item;
            });

        // --- RETURN TO VUE ---
        return Inertia::render('OperatingUnits', [
            'charts' => [
                'overall_sentiment'   => $overallSentiment,
                'sentiment_by_topic'  => $sentimentByTopic,
                'top_negative'        => $topNegative,
                'top_positive'        => $topPositive,
            ],
            'recent_feedback' => $recentFeedback,
            'availableYears'  => $availableYears, // Send years list to dropdown
            'filters' => [
                'unit'    => $request->unit ?? "",
                'service' => $request->service ?? "All Services",
                'year'    => $request->year ?? "" // Persist year filter
            ]
        ]);
    }
}