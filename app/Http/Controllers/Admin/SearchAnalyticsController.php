<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SearchAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('period', '30d');
        $periods = [
            '7d' => ['label' => 'Son 7 gün', 'start' => now()->subDays(7)],
            '30d' => ['label' => 'Son 30 gün', 'start' => now()->subDays(30)],
            '90d' => ['label' => 'Son 90 gün', 'start' => now()->subDays(90)],
        ];

        if (! array_key_exists($period, $periods)) {
            $period = '30d';
        }

        $since = $periods[$period]['start'];

        $topQueries = SearchQuery::query()
            ->where('searched_at', '>=', $since)
            ->select(
                'normalized',
                DB::raw('MAX(query) as sample_query'),
                DB::raw('COUNT(*) as searches'),
                DB::raw('ROUND(AVG(results_count), 1) as avg_results'),
            )
            ->groupBy('normalized')
            ->orderByDesc('searches')
            ->limit(25)
            ->get();

        $zeroResults = SearchQuery::query()
            ->where('searched_at', '>=', $since)
            ->where('results_count', 0)
            ->select(
                'normalized',
                DB::raw('MAX(query) as sample_query'),
                DB::raw('COUNT(*) as searches'),
            )
            ->groupBy('normalized')
            ->orderByDesc('searches')
            ->limit(15)
            ->get();

        $totals = SearchQuery::query()
            ->where('searched_at', '>=', $since)
            ->selectRaw('COUNT(*) as total_searches')
            ->selectRaw('COUNT(DISTINCT normalized) as unique_queries')
            ->selectRaw('SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) as zero_result_searches')
            ->first();

        return view('admin.search-analytics.index', [
            'period' => $period,
            'periodLabel' => $periods[$period]['label'],
            'topQueries' => $topQueries,
            'zeroResults' => $zeroResults,
            'totals' => $totals,
        ]);
    }
}
