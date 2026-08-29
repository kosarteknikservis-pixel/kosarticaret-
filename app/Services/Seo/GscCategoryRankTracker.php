<?php

namespace App\Services\Seo;

class GscCategoryRankTracker
{
    /**
     * @param  list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>  $queries
     * @param  list<array{keyword: string, target_position: int}>  $trackedKeywords
     * @return array{
     *     tracked_at: string,
     *     keywords: list<array{
     *         keyword: string,
     *         target_position: int,
     *         matched_query: ?string,
     *         position: ?float,
     *         clicks: int,
     *         impressions: int,
     *         status: string
     *     }>
     * }
     */
    public function track(array $queries, array $trackedKeywords): array
    {
        $index = [];
        foreach ($queries as $row) {
            $index[$this->normalize($row['query'])] = $row;
        }

        $results = [];

        foreach ($trackedKeywords as $tracked) {
            $keyword = (string) $tracked['keyword'];
            $target = (int) ($tracked['target_position'] ?? 20);
            $match = $this->findMatch($keyword, $index);

            if ($match === null) {
                $results[] = [
                    'keyword' => $keyword,
                    'target_position' => $target,
                    'matched_query' => null,
                    'position' => null,
                    'clicks' => 0,
                    'impressions' => 0,
                    'status' => 'no_data',
                ];

                continue;
            }

            $position = (float) $match['position'];
            $results[] = [
                'keyword' => $keyword,
                'target_position' => $target,
                'matched_query' => $match['query'],
                'position' => $position,
                'clicks' => (int) $match['clicks'],
                'impressions' => (int) $match['impressions'],
                'status' => $this->statusFor($position, $target),
            ];
        }

        usort($results, fn (array $a, array $b): int => ($a['position'] ?? 999) <=> ($b['position'] ?? 999));

        return [
            'tracked_at' => now()->toIso8601String(),
            'keywords' => $results,
        ];
    }

    /** @param  array<string, array{query: string, clicks: int, impressions: int, ctr: float, position: float}>  $index */
    private function findMatch(string $keyword, array $index): ?array
    {
        $normalized = $this->normalize($keyword);
        if (isset($index[$normalized])) {
            return $index[$normalized];
        }

        $best = null;
        $bestScore = 0;

        foreach ($index as $query => $row) {
            $score = 0;
            if ($query === $normalized) {
                $score = 100;
            } elseif (str_contains($query, $normalized) || str_contains($normalized, $query)) {
                $score = 80 - abs(mb_strlen($query) - mb_strlen($normalized));
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        return $bestScore >= 60 ? $best : null;
    }

    private function statusFor(float $position, int $target): string
    {
        if ($position <= 5) {
            return 'top_5';
        }

        if ($position <= 10) {
            return 'top_10';
        }

        if ($position <= $target) {
            return 'on_target';
        }

        if ($position <= 20) {
            return 'page_2';
        }

        return 'needs_work';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = str_replace(['ı', 'İ'], 'i', $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
