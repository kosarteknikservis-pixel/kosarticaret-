<?php

namespace App\Services;

use App\Models\PageSpeedAudit;
use App\Models\SiteSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PageSpeedInsightsService
{
    private const API_URL = 'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed';

    /** @var list<string> */
    private const OPPORTUNITY_AUDITS = [
        'render-blocking-resources',
        'unused-css-rules',
        'unused-javascript',
        'modern-image-formats',
        'uses-optimized-images',
        'uses-responsive-images',
        'offscreen-images',
        'efficient-animated-content',
        'uses-text-compression',
        'server-response-time',
        'total-byte-weight',
        'dom-size',
    ];

    public static function isConfigured(): bool
    {
        return filled(self::apiKey());
    }

    public static function apiKey(): string
    {
        $fromPanel = trim((string) SiteSetting::get('pagespeed_api_key', ''));

        if ($fromPanel !== '') {
            return $fromPanel;
        }

        return trim((string) config('kosar.pagespeed.api_key', ''));
    }

    /**
     * @return array{
     *     performance_score: ?int,
     *     fcp_ms: ?int,
     *     lcp_ms: ?int,
     *     cls: ?float,
     *     tbt_ms: ?int,
     *     speed_index_ms: ?int,
     *     field_lcp_p75_ms: ?int,
     *     field_cls_p75: ?float,
     *     field_inp_p75_ms: ?int,
     *     field_overall_category: ?string,
     *     opportunities: list<array{title: string, description: string, savings_ms: ?int, savings_bytes: ?int}>
     * }
     */
    public function parseResponse(array $payload): array
    {
        $lighthouse = $payload['lighthouseResult'] ?? [];
        $audits = $lighthouse['audits'] ?? [];
        $categories = $lighthouse['categories'] ?? [];

        $scoreRaw = $categories['performance']['score'] ?? null;
        $performanceScore = is_numeric($scoreRaw) ? (int) round((float) $scoreRaw * 100) : null;

        $field = $this->parseFieldMetrics($payload['loadingExperience']['metrics'] ?? []);
        $fieldCategory = $payload['loadingExperience']['overall_category'] ?? null;
        if (! is_string($fieldCategory) || $fieldCategory === '') {
            $fieldCategory = $payload['originLoadingExperience']['overall_category'] ?? null;
            if ((! $field || $field['field_lcp_p75_ms'] === null) && isset($payload['originLoadingExperience']['metrics'])) {
                $field = $this->parseFieldMetrics($payload['originLoadingExperience']['metrics']);
            }
        }

        return [
            'performance_score' => $performanceScore,
            'fcp_ms' => $this->auditMs($audits, 'first-contentful-paint'),
            'lcp_ms' => $this->auditMs($audits, 'largest-contentful-paint'),
            'cls' => $this->auditFloat($audits, 'cumulative-layout-shift'),
            'tbt_ms' => $this->auditMs($audits, 'total-blocking-time'),
            'speed_index_ms' => $this->auditMs($audits, 'speed-index'),
            'field_lcp_p75_ms' => $field['field_lcp_p75_ms'],
            'field_cls_p75' => $field['field_cls_p75'],
            'field_inp_p75_ms' => $field['field_inp_p75_ms'],
            'field_overall_category' => is_string($fieldCategory) ? $fieldCategory : null,
            'opportunities' => $this->parseOpportunities($audits),
        ];
    }

    /**
     * @param  array{key: string, label: string, url: string}  $target
     */
    public function auditAndStore(array $target, string $strategy): PageSpeedAudit
    {
        if (! self::isConfigured()) {
            throw new RuntimeException('PageSpeed API anahtarı tanımlı değil.');
        }

        if (! in_array($strategy, ['mobile', 'desktop'], true)) {
            throw new RuntimeException('Geçersiz strategy değeri.');
        }

        try {
            $payload = $this->fetch($target['url'], $strategy);
            $parsed = $this->parseResponse($payload);

            return PageSpeedAudit::query()->create([
                'page_key' => $target['key'],
                'label' => $target['label'],
                'url' => $target['url'],
                'strategy' => $strategy,
                'performance_score' => $parsed['performance_score'],
                'fcp_ms' => $parsed['fcp_ms'],
                'lcp_ms' => $parsed['lcp_ms'],
                'cls' => $parsed['cls'],
                'tbt_ms' => $parsed['tbt_ms'],
                'speed_index_ms' => $parsed['speed_index_ms'],
                'field_lcp_p75_ms' => $parsed['field_lcp_p75_ms'],
                'field_cls_p75' => $parsed['field_cls_p75'],
                'field_inp_p75_ms' => $parsed['field_inp_p75_ms'],
                'field_overall_category' => $parsed['field_overall_category'],
                'opportunities' => $parsed['opportunities'],
                'error_message' => null,
                'measured_at' => now(),
            ]);
        } catch (RequestException $e) {
            $message = $this->humanizeApiError($e);

            return PageSpeedAudit::query()->create([
                'page_key' => $target['key'],
                'label' => $target['label'],
                'url' => $target['url'],
                'strategy' => $strategy,
                'error_message' => $message,
                'measured_at' => now(),
            ]);
        }
    }

    public function latestFor(string $pageKey, string $strategy): ?PageSpeedAudit
    {
        return PageSpeedAudit::query()
            ->where('page_key', $pageKey)
            ->where('strategy', $strategy)
            ->whereNull('error_message')
            ->latest('measured_at')
            ->first();
    }

    public function isFresh(?PageSpeedAudit $audit): bool
    {
        if (! $audit || $audit->error_message) {
            return false;
        }

        $minutes = (int) config('kosar.pagespeed.cache_minutes', 360);

        return $audit->measured_at instanceof Carbon
            && $audit->measured_at->greaterThan(now()->subMinutes($minutes));
    }

    /** @return array<string, mixed> */
    private function fetch(string $url, string $strategy): array
    {
        $response = Http::timeout((int) config('kosar.pagespeed.timeout_seconds', 120))
            ->acceptJson()
            ->get(self::API_URL, [
                'url' => $url,
                'key' => self::apiKey(),
                'strategy' => $strategy,
                'category' => 'performance',
                'locale' => 'tr',
            ]);

        $response->throw();

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('PageSpeed API geçersiz yanıt döndürdü.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{field_lcp_p75_ms: ?int, field_cls_p75: ?float, field_inp_p75_ms: ?int}
     */
    private function parseFieldMetrics(array $metrics): array
    {
        return [
            'field_lcp_p75_ms' => $this->fieldPercentile($metrics, 'LARGEST_CONTENTFUL_PAINT_MS'),
            'field_cls_p75' => $this->fieldClsPercentile($metrics),
            'field_inp_p75_ms' => $this->fieldPercentile($metrics, 'INTERACTION_TO_NEXT_PAINT')
                ?? $this->fieldPercentile($metrics, 'EXPERIMENTAL_INTERACTION_TO_NEXT_PAINT'),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function fieldPercentile(array $metrics, string $key): ?int
    {
        $percentile = data_get($metrics, "{$key}.percentile");

        return is_numeric($percentile) ? (int) round((float) $percentile) : null;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function fieldClsPercentile(array $metrics): ?float
    {
        $percentile = data_get($metrics, 'CUMULATIVE_LAYOUT_SHIFT_SCORE.percentile');

        if (! is_numeric($percentile)) {
            return null;
        }

        $value = (float) $percentile;

        return $value > 1 ? round($value / 100, 4) : round($value, 4);
    }

    /**
     * @param  array<string, mixed>  $audits
     */
    private function auditMs(array $audits, string $id): ?int
    {
        $numeric = data_get($audits, "{$id}.numericValue");

        return is_numeric($numeric) ? (int) round((float) $numeric) : null;
    }

    /**
     * @param  array<string, mixed>  $audits
     */
    private function auditFloat(array $audits, string $id): ?float
    {
        $numeric = data_get($audits, "{$id}.numericValue");

        return is_numeric($numeric) ? round((float) $numeric, 4) : null;
    }

    /**
     * @param  array<string, mixed>  $audits
     * @return list<array{title: string, description: string, savings_ms: ?int, savings_bytes: ?int}>
     */
    private function parseOpportunities(array $audits): array
    {
        $items = [];

        foreach (self::OPPORTUNITY_AUDITS as $auditId) {
            $audit = $audits[$auditId] ?? null;
            if (! is_array($audit)) {
                continue;
            }

            $score = $audit['score'] ?? null;
            if ($score !== null && (float) $score >= 0.99) {
                continue;
            }

            $savingsMs = data_get($audit, 'details.overallSavingsMs');
            $savingsBytes = data_get($audit, 'details.overallSavingsBytes');

            if (! is_numeric($savingsMs) && ! is_numeric($savingsBytes)) {
                continue;
            }

            $items[] = [
                'title' => (string) ($audit['title'] ?? $auditId),
                'description' => strip_tags((string) ($audit['description'] ?? '')),
                'savings_ms' => is_numeric($savingsMs) ? (int) round((float) $savingsMs) : null,
                'savings_bytes' => is_numeric($savingsBytes) ? (int) round((float) $savingsBytes) : null,
            ];
        }

        usort($items, function (array $a, array $b) {
            return ($b['savings_ms'] ?? 0) <=> ($a['savings_ms'] ?? 0);
        });

        return array_slice($items, 0, 6);
    }

    private function humanizeApiError(RequestException $exception): string
    {
        $body = $exception->response?->json();
        $message = data_get($body, 'error.message');

        if (is_string($message) && $message !== '') {
            if (str_contains($message, 'API key not valid')) {
                return 'Google PageSpeed API anahtarı geçersiz. Site ayarları → Entegrasyonlar bölümündeki anahtarı kontrol edin.';
            }

            return $message;
        }

        return 'PageSpeed API isteği başarısız oldu (HTTP '.$exception->response?->status().').';
    }
}
