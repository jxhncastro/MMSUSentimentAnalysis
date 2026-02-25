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
        // 1. Base Query
        $query = Feedback::query();

        // 2. Filter by Operating Unit (Office)
        if ($request->filled('unit')) {
            $query->where(DB::raw('LOWER(TRIM(office))'), strtolower(trim($request->unit)));
        }

        // 3. Filter by Specific Service (using 'services_availed' column)
        // We use LIKE because one feedback row might contain multiple services
        if ($request->filled('service') && $request->service !== 'All Services') {
            $query->where('services_availed', 'LIKE', '%' . trim($request->service) . '%');
        }

        // --- CHART 1: OVERALL SATISFACTION (Donut) ---
        $rawSentiment = (clone $query)
            ->select('sentiment', DB::raw('count(*) as count'))
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        $overallSentiment = [
            'Positive' => ($rawSentiment['Positive'] ?? 0) + ($rawSentiment['positive'] ?? 0) + ($rawSentiment['POSITIVE'] ?? 0),
            'Negative' => ($rawSentiment['Negative'] ?? 0) + ($rawSentiment['negative'] ?? 0) + ($rawSentiment['NEGATIVE'] ?? 0),
            'Neutral'  => ($rawSentiment['Neutral']  ?? 0) + ($rawSentiment['neutral']  ?? 0) + ($rawSentiment['NEUTRAL'] ?? 0),
        ];

        // --- CHART 2: SENTIMENT BY TOPIC (Stacked Bar) ---
        $sentimentByTopic = (clone $query)
            ->select('topic', 'sentiment', DB::raw('count(*) as count'))
            ->whereNotNull('topic')
            ->where('topic', '!=', 'General')
            ->groupBy('topic', 'sentiment')
            ->get()
            ->groupBy('topic')
            ->map(function ($group) {
                return [
                    'positive' => $group->whereIn('sentiment', ['Positive', 'positive', 'POSITIVE'])->sum('count'),
                    'negative' => $group->whereIn('sentiment', ['Negative', 'negative', 'NEGATIVE'])->sum('count'),
                    'neutral'  => $group->whereIn('sentiment', ['Neutral', 'neutral', 'NEUTRAL'])->sum('count'),
                ];
            })->toArray();

        // --- CHART 3 & 4: GLOBAL TOP LISTS (Only shown when no unit is selected) ---
        $topNegative = [];
        $topPositive = [];
        
        if (!$request->filled('unit')) {
            $topNegative = Feedback::whereIn('sentiment', ['Negative', 'negative', 'NEGATIVE'])
                ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
                ->select('office', DB::raw('count(*) as count'))
                ->groupBy('office')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            $topPositive = Feedback::whereIn('sentiment', ['Positive', 'positive', 'POSITIVE'])
                ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
                ->select('office', DB::raw('count(*) as count'))
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
                $item->sentiment = ucfirst(strtolower($item->sentiment)); 
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
            // Use filled() check to ensure we only pass back valid filters
            'filters' => [
                'unit' => $request->unit ?? "",
                'service' => $request->service ?? "All Services"
            ]
        ]);
    }
}