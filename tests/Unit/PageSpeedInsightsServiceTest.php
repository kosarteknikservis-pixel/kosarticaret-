<?php

namespace Tests\Unit;

use App\Services\PageSpeedInsightsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageSpeedInsightsServiceTest extends TestCase
{
    #[Test]
    public function it_parses_lighthouse_and_field_metrics_from_pagespeed_payload(): void
    {
        $service = new PageSpeedInsightsService;

        $parsed = $service->parseResponse([
            'loadingExperience' => [
                'overall_category' => 'AVERAGE',
                'metrics' => [
                    'LARGEST_CONTENTFUL_PAINT_MS' => ['percentile' => 2800],
                    'CUMULATIVE_LAYOUT_SHIFT_SCORE' => ['percentile' => 12],
                    'INTERACTION_TO_NEXT_PAINT' => ['percentile' => 190],
                ],
            ],
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.74],
                ],
                'audits' => [
                    'first-contentful-paint' => ['numericValue' => 1200],
                    'largest-contentful-paint' => ['numericValue' => 3100],
                    'cumulative-layout-shift' => ['numericValue' => 0.08],
                    'total-blocking-time' => ['numericValue' => 240],
                    'speed-index' => ['numericValue' => 4200],
                    'render-blocking-resources' => [
                        'title' => 'Oluşturmayı engelleyen kaynaklar',
                        'description' => 'Test',
                        'score' => 0.2,
                        'details' => ['overallSavingsMs' => 850],
                    ],
                ],
            ],
        ]);

        $this->assertSame(74, $parsed['performance_score']);
        $this->assertSame(1200, $parsed['fcp_ms']);
        $this->assertSame(3100, $parsed['lcp_ms']);
        $this->assertSame(0.08, $parsed['cls']);
        $this->assertSame(240, $parsed['tbt_ms']);
        $this->assertSame(4200, $parsed['speed_index_ms']);
        $this->assertSame(2800, $parsed['field_lcp_p75_ms']);
        $this->assertSame(0.12, $parsed['field_cls_p75']);
        $this->assertSame(190, $parsed['field_inp_p75_ms']);
        $this->assertSame('AVERAGE', $parsed['field_overall_category']);
        $this->assertNotEmpty($parsed['opportunities']);
    }
}
