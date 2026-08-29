<?php

namespace Tests\Unit;

use App\Services\Seo\GscCategoryRankTracker;
use App\Services\Seo\SeoDriftScanner;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoMonitoringTest extends TestCase
{
    #[Test]
    public function it_tracks_category_keywords_from_gsc_queries(): void
    {
        $tracker = new GscCategoryRankTracker;

        $report = $tracker->track(
            [
                ['query' => 'hidrofor fiyatları', 'clicks' => 12, 'impressions' => 400, 'ctr' => 0.03, 'position' => 8.2],
                ['query' => 'dalgıç pompa', 'clicks' => 3, 'impressions' => 90, 'ctr' => 0.03, 'position' => 14.5],
            ],
            [
                ['keyword' => 'hidrofor fiyatları', 'target_position' => 10],
                ['keyword' => 'jakuzi pompası', 'target_position' => 20],
            ]
        );

        $this->assertSame('top_10', $report['keywords'][0]['status']);
        $this->assertSame('hidrofor fiyatları', $report['keywords'][0]['keyword']);
        $this->assertSame('no_data', $report['keywords'][1]['status']);
    }

    #[Test]
    public function it_detects_seo_signal_regressions(): void
    {
        $scanner = new SeoDriftScanner;

        $baseline = [
            'pages' => [
                [
                    'key' => 'home',
                    'title' => 'Koşar',
                    'h1' => 'Su ve Hava Sistemleri',
                    'h1_count' => 1,
                    'canonical' => 'https://kosarticaret.com/',
                    'json_ld_types' => ['Organization', 'WebSite'],
                ],
            ],
        ];

        $current = [
            'pages' => [
                [
                    'key' => 'home',
                    'title' => 'Koşar',
                    'h1' => 'Farkli Baslik',
                    'h1_count' => 2,
                    'canonical' => 'https://kosarticaret.com/',
                    'json_ld_types' => ['Organization', 'WebSite'],
                ],
            ],
        ];

        $regressions = $scanner->diff($baseline, $current);

        $this->assertCount(1, $regressions);
        $fields = collect($regressions[0]['changes'])->pluck('field')->all();
        $this->assertContains('h1', $fields);
        $this->assertContains('h1_count', $fields);
    }
}
