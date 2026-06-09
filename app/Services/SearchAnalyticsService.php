<?php

namespace App\Services;

use App\Models\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchAnalyticsService
{
    public function record(Request $request, string $query, int $resultsCount): void
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) < 2) {
            return;
        }

        SearchQuery::query()->create([
            'query' => Str::limit($query, 255, ''),
            'normalized' => Str::limit(mb_strtolower($query, 'UTF-8'), 255, ''),
            'results_count' => max(0, $resultsCount),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'searched_at' => now(),
        ]);
    }
}
