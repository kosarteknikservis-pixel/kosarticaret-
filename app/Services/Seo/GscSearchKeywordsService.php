<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\File;

class GscSearchKeywordsService
{
    /** @var list<int> */
    public const LIVE_PERIODS = [7, 28, 90];

    public function __construct(private GscCategoryRankTracker $rankTracker) {}

    /**
     * @return array{
     *     snapshot: array<string, mixed>,
     *     live: array<string, array<string, mixed>>,
     *     tracked: array<string, mixed>,
     *     gsc_url: string,
     *     gsc_period: int
     * }
     */
    public function panelData(?int $gscPeriod = null): array
    {
        $gscPeriod = $this->normalizePeriod($gscPeriod);
        $snapshot = $this->latestSnapshot();
        $live = [];

        foreach (self::LIVE_PERIODS as $days) {
            $live[(string) $days] = $this->cachedPeriod($days);
        }

        $activeLive = $live[(string) $gscPeriod] ?? null;
        $trackedSource = $activeLive ?? $snapshot;

        return [
            'snapshot' => $snapshot,
            'live' => $live,
            'tracked' => $this->trackedKeywordsReport($trackedSource),
            'gsc_url' => $this->searchConsoleUrl(),
            'gsc_period' => $gscPeriod,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function latestSnapshot(): array
    {
        $path = storage_path('seo-reports/gsc-performance-latest.json');

        if (! is_readable($path)) {
            return $this->emptyReport('snapshot', 'Aylık GSC özeti henüz oluşturulmadı. `seo:fetch-monthly-data` veya `seo:import-gsc-performance` çalıştırın.');
        }

        $data = $this->readJson($path);

        if ($data === null) {
            return $this->emptyReport('snapshot', 'GSC özet dosyası okunamadı.');
        }

        return $this->normalizeReport($data, 'snapshot');
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedPeriod(int $days): array
    {
        $path = $this->periodCachePath($days);

        if (! is_readable($path)) {
            return $this->emptyReport('live', sprintf('Son %d günlük GSC verisi henüz çekilmedi. Günlük cron veya `seo:fetch-gsc-keywords` bekleniyor.', $days));
        }

        $data = $this->readJson($path);

        if ($data === null) {
            return $this->emptyReport('live', sprintf('Son %d günlük GSC önbelleği okunamadı.', $days));
        }

        return $this->normalizeReport($data, 'live');
    }

    public function periodCachePath(int $days): string
    {
        return storage_path('seo-reports/gsc-keywords-'.$days.'.json');
    }

    /**
     * Analytics dönemini GSC cache dönemine eşler (Search Console ile aynı kaynak).
     *
     * @return array{
     *     days: int,
     *     label: string,
     *     available: bool,
     *     clicks: int,
     *     impressions: int,
     *     ctr: float|null,
     *     period_label: ?string,
     *     message: ?string
     * }
     */
    public function summaryForAnalyticsPeriod(string $analyticsPeriod): array
    {
        $days = match ($analyticsPeriod) {
            'today', 'week' => 7,
            'month' => 28,
            'year' => 90,
            default => 28,
        };

        $label = match ($days) {
            7 => 'Son 7 gün',
            28 => 'Son 28 gün',
            90 => 'Son 90 gün',
            default => 'Son '.$days.' gün',
        };

        $report = $this->cachedPeriod($days);

        if (! ($report['available'] ?? false)) {
            return [
                'days' => $days,
                'label' => $label,
                'available' => false,
                'clicks' => 0,
                'impressions' => 0,
                'ctr' => null,
                'period_label' => null,
                'message' => $report['message'] ?? 'GSC verisi henüz yok.',
            ];
        }

        $clicks = (int) ($report['totals']['clicks'] ?? 0);
        $impressions = (int) ($report['totals']['impressions'] ?? 0);

        return [
            'days' => $days,
            'label' => $label,
            'available' => true,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 1) : null,
            'period_label' => $report['period_label'] ?? null,
            'message' => null,
        ];
    }

    public function snapshotPath(): string
    {
        return storage_path('seo-reports/gsc-performance-latest.json');
    }

    /**
     * @param  array<string, mixed>  $cache
     */
    public function writeSnapshot(array $cache): void
    {
        $path = $this->snapshotPath();
        File::ensureDirectoryExists(dirname($path));

        $snapshot = [
            'imported_at' => $cache['fetched_at'] ?? now()->toIso8601String(),
            'source' => $cache['source'] ?? 'api:seo:fetch-gsc-keywords',
            'period' => $cache['period'] ?? [],
            'queries' => $cache['queries'] ?? [],
            'opportunities' => [],
            'category_tracking' => $cache['category_tracking'] ?? [],
            'totals' => $cache['totals'] ?? [],
        ];

        file_put_contents(
            $path,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function searchConsoleUrl(): string
    {
        $site = (string) config('google_seo.gsc_site_url', 'https://kosarticaret.com/');

        return 'https://search.google.com/search-console/performance/search-analytics?resource_id='.rawurlencode($site);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function buildCachePayload(array $payload): array
    {
        $queries = $this->normalizeQueries($payload['queries'] ?? []);

        return [
            'fetched_at' => now()->toIso8601String(),
            'source' => (string) ($payload['source'] ?? 'api:seo:fetch-gsc-keywords'),
            'site_url' => (string) ($payload['site_url'] ?? config('google_seo.gsc_site_url')),
            'period' => $payload['period'] ?? [],
            'totals' => [
                'clicks' => (int) ($payload['totals']['clicks'] ?? 0),
                'impressions' => (int) ($payload['totals']['impressions'] ?? 0),
                'query_count' => count($queries),
            ],
            'queries' => $queries,
            'category_tracking' => $this->rankTracker->track(
                $queries,
                config('seo_monitoring.category_keywords', [])
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $source
     * @return array<string, mixed>
     */
    private function trackedKeywordsReport(?array $source): array
    {
        if ($source === null || ! ($source['available'] ?? false)) {
            return [
                'available' => false,
                'message' => 'Ticari kelime takibi için GSC verisi bekleniyor.',
                'keywords' => [],
                'tracked_at' => null,
                'source_label' => null,
            ];
        }

        $tracking = $source['category_tracking'] ?? null;

        if (! is_array($tracking) || ($tracking['keywords'] ?? []) === []) {
            $queries = $source['queries'] ?? [];
            $tracking = $this->rankTracker->track(
                $queries,
                config('seo_monitoring.category_keywords', [])
            );
        }

        return [
            'available' => true,
            'message' => null,
            'keywords' => $tracking['keywords'] ?? [],
            'tracked_at' => $tracking['tracked_at'] ?? ($source['imported_at'] ?? null),
            'source_label' => $source['source_label'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeReport(array $data, string $kind): array
    {
        $queries = $this->normalizeQueries($data['queries'] ?? []);
        $period = $data['period'] ?? [];
        $importedAt = $data['imported_at'] ?? $data['fetched_at'] ?? null;
        $source = (string) ($data['source'] ?? ($kind === 'snapshot' ? 'snapshot' : 'cache'));

        if ($queries === [] && ($data['totals']['query_count'] ?? 0) === 0) {
            return $this->emptyReport($kind, 'Bu dönem için GSC sorgu verisi bulunamadı.');
        }

        $categoryTracking = $data['category_tracking'] ?? $this->rankTracker->track(
            $queries,
            config('seo_monitoring.category_keywords', [])
        );

        return [
            'available' => true,
            'kind' => $kind,
            'message' => null,
            'imported_at' => $importedAt,
            'source' => $source,
            'source_label' => $this->sourceLabel($source),
            'period' => $period,
            'period_label' => $this->periodLabel($period),
            'totals' => [
                'clicks' => (int) ($data['totals']['clicks'] ?? 0),
                'impressions' => (int) ($data['totals']['impressions'] ?? 0),
                'query_count' => (int) ($data['totals']['query_count'] ?? count($queries)),
            ],
            'queries' => array_slice($queries, 0, 25),
            'category_tracking' => $categoryTracking,
        ];
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>
     */
    private function normalizeQueries(array $rows): array
    {
        $queries = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $query = trim((string) ($row['query'] ?? ''));

            if ($query === '') {
                continue;
            }

            $queries[] = [
                'query' => $query,
                'clicks' => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr' => (float) ($row['ctr'] ?? 0),
                'position' => (float) ($row['position'] ?? 0),
            ];
        }

        usort($queries, fn (array $a, array $b): int => $b['clicks'] <=> $a['clicks']);

        return $queries;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(string $kind, string $message): array
    {
        return [
            'available' => false,
            'kind' => $kind,
            'message' => $message,
            'imported_at' => null,
            'source' => null,
            'source_label' => null,
            'period' => [],
            'period_label' => null,
            'totals' => [
                'clicks' => 0,
                'impressions' => 0,
                'query_count' => 0,
            ],
            'queries' => [],
            'category_tracking' => [
                'tracked_at' => null,
                'keywords' => [],
            ],
        ];
    }

    private function sourceLabel(string $source): string
    {
        return match (true) {
            str_contains($source, 'import') => 'Manuel GSC export',
            str_contains($source, 'fetch-monthly') => 'Aylık B1 API',
            str_contains($source, 'fetch-gsc-keywords') => 'Günlük GSC API',
            default => 'GSC özeti',
        };
    }

    /**
     * @param  array<string, mixed>  $period
     */
    private function periodLabel(array $period): ?string
    {
        $start = $period['start'] ?? null;
        $end = $period['end'] ?? null;
        $days = $period['days'] ?? null;

        if ($start && $end) {
            return $start.' → '.$end.($days ? ' ('.$days.' gün)' : '');
        }

        if ($days) {
            return 'Son '.$days.' gün';
        }

        return null;
    }

    private function normalizePeriod(?int $period): int
    {
        if ($period !== null && in_array($period, self::LIVE_PERIODS, true)) {
            return $period;
        }

        return 28;
    }
}
