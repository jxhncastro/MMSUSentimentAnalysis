<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperatingUnitController extends Controller
{
    /**
     * Strict list of survey noise to exclude from rankings.
     */
    protected $excludedUnits = [
        'strongly agree', 'agree', 'neither agree nor disagree', 
        'disagree', 'strongly disagree', 'n/a', 'na', 'none', 
        'general service', 'unknown', 'select office'
    ];

    public function index(Request $request)
    {
        // 1. Base Query
        $query = Feedback::query();

        // --- ROBUST FILTERING (Fixes "0 Data" issue) ---
        // We use LOWER() and TRIM() to ensure " University Registrar's Office " matches "University Registrar's Office"
        if ($request->unit) {
            $query->where(DB::raw('LOWER(TRIM(office))'), strtolower(trim($request->unit)));
        }

        // --- CHART 1: OVERALL SATISFACTION (Donut) ---
        // We fetch raw counts first, then normalize keys (Positive/positive) in PHP
        $rawSentiment = (clone $query)
            ->select('sentiment', DB::raw('count(*) as count'))
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        // Normalize Keys to Title Case (Positive, Negative, Neutral)
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
            });

        // --- CHART 3: TOP NEGATIVE OFFICES (Global View Only) ---
        // Only calculate this if NO unit is selected (to show global hotspots)
        $topNegative = [];
        if (!$request->unit) {
            $topNegative = Feedback::whereIn('sentiment', ['Negative', 'negative', 'NEGATIVE'])
                ->whereNotIn(DB::raw('LOWER(TRIM(office))'), $this->excludedUnits)
                ->select('office', DB::raw('count(*) as count'))
                ->groupBy('office')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
        }

        // --- CHART 4: TOP POSITIVE OFFICES (Global View Only) ---
        $topPositive = [];
        if (!$request->unit) {
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
                // Normalize sentiment string for the UI badges
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
            'filters' => $request->only(['unit']) // Pass back the selected unit
        ]);
    }
}