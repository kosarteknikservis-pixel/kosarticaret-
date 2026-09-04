<?php

namespace Tests\Unit;

use App\Services\Seo\GscCategoryRankTracker;
use App\Services\Seo\GscSearchKeywordsService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GscSearchKeywordsServiceTest extends TestCase
{
    #[Test]
    public function it_reads_snapshot_and_builds_panel_data(): void
    {
        $dir = storage_path('seo-reports');
        File::ensureDirectoryExists($dir);

        $snapshotPath = $dir.'/gsc-performance-latest.json';
        $livePath = $dir.'/gsc-keywords-28.json';

        File::put($snapshotPath, json_encode([
            'imported_at' => '2026-09-01T10:00:00+03:00',
            'source' => 'api:seo:fetch-monthly-data',
            'period' => ['days' => 90, 'start' => '2026-06-01', 'end' => '2026-08-31'],
            'totals' => ['clicks' => 120, 'impressions' => 5000, 'query_count' => 2],
            'queries' => [
                ['query' => 'hidrofor fiyatları', 'clicks' => 20, 'impressions' => 800, 'ctr' => 0.025, 'position' => 7.5],
                ['query' => 'dalgıç pompa', 'clicks' => 10, 'impressions' => 400, 'ctr' => 0.025, 'position' => 12.0],
            ],
        ], JSON_UNESCAPED_UNICODE));

        File::put($livePath, json_encode([
            'fetched_at' => '2026-09-02T05:05:00+03:00',
            'source' => 'api:seo:fetch-gsc-keywords',
            'period' => ['days' => 28, 'start' => '2026-08-05', 'end' => '2026-09-01'],
            'totals' => ['clicks' => 45, 'impressions' => 1200, 'query_count' => 1],
            'queries' => [
                ['query' => 'jakuzi pompası', 'clicks' => 5, 'impressions' => 120, 'ctr' => 0.04, 'position' => 18.0],
            ],
            'category_tracking' => [
                'tracked_at' => '2026-09-02T05:05:00+03:00',
                'keywords' => [
                    ['keyword' => 'jakuzi pompası', 'target_position' => 20, 'matched_query' => 'jakuzi pompası', 'position' => 18.0, 'clicks' => 5, 'impressions' => 120, 'status' => 'on_target'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE));

        $service = new GscSearchKeywordsService(new GscCategoryRankTracker);
        $panel = $service->panelData(28);

        $this->assertTrue($panel['snapshot']['available']);
        $this->assertSame(120, $panel['snapshot']['totals']['clicks']);
        $this->assertTrue($panel['live']['28']['available']);
        $this->assertSame('jakuzi pompası', $panel['live']['28']['queries'][0]['query']);
        $this->assertTrue($panel['tracked']['available']);
        $this->assertSame('on_target', $panel['tracked']['keywords'][0]['status']);
        $this->assertStringContainsString('search-console', $panel['gsc_url']);

        $summary = $service->summaryForAnalyticsPeriod('month');
        $this->assertTrue($summary['available']);
        $this->assertSame(28, $summary['days']);
        $this->assertSame(45, $summary['clicks']);
        $this->assertSame(1200, $summary['impressions']);

        File::delete($snapshotPath);
        File::delete($livePath);
    }

    #[Test]
    public function it_returns_helpful_empty_states_when_cache_missing(): void
    {
        $missingPath = storage_path('seo-reports/gsc-keywords-7.json');
        if (is_file($missingPath)) {
            File::delete($missingPath);
        }

        $service = new GscSearchKeywordsService(new GscCategoryRankTracker);
        $report = $service->cachedPeriod(7);

        $this->assertFalse($report['available']);
        $this->assertStringContainsString('7 günlük', (string) $report['message']);
    }

    #[Test]
    public function it_builds_cache_payload_with_category_tracking(): void
    {
        $service = new GscSearchKeywordsService(new GscCategoryRankTracker);

        $payload = $service->buildCachePayload([
            'source' => 'api:seo:fetch-gsc-keywords',
            'site_url' => 'https://kosarticaret.com/',
            'period' => ['days' => 7, 'start' => '2026-08-26', 'end' => '2026-09-01'],
            'totals' => ['clicks' => 3, 'impressions' => 90],
            'queries' => [
                ['query' => 'hidrofor', 'clicks' => 3, 'impressions' => 90, 'ctr' => 0.03, 'position' => 11.0],
            ],
        ]);

        $this->assertSame(1, $payload['totals']['query_count']);
        $this->assertNotEmpty($payload['category_tracking']['keywords']);
    }
}
