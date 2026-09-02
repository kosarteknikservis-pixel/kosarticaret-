<?php

namespace App\Services\Seo;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GscSearchAnalyticsClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    /**
     * @return array{
     *     fetched_at: string,
     *     source: string,
     *     site_url: string,
     *     period: array{days: int, start: string, end: string},
     *     totals: array{clicks: int, impressions: int, query_count: int},
     *     queries: list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>
     * }
     */
    public function fetchQueries(int $days): array
    {
        $siteUrl = $this->resolveSiteUrl();
        $end = now()->subDay()->startOfDay();
        $start = $end->copy()->subDays($days - 1);
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $queries = $this->querySearchAnalytics($siteUrl, $startDate, $endDate);
        $totals = $this->siteTotals($siteUrl, $startDate, $endDate);
        $totals['query_count'] = count($queries);

        return [
            'fetched_at' => now()->toIso8601String(),
            'source' => 'api:seo:fetch-gsc-keywords',
            'site_url' => $siteUrl,
            'period' => [
                'days' => $days,
                'start' => $startDate,
                'end' => $endDate,
            ],
            'totals' => $totals,
            'queries' => array_slice($queries, 0, 100),
        ];
    }

    /**
     * @return list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>
     */
    private function querySearchAnalytics(string $siteUrl, string $startDate, string $endDate): array
    {
        $response = $this->apiPost(
            '/webmasters/v3/sites/'.rawurlencode($siteUrl).'/searchAnalytics/query',
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => ['query'],
                'rowLimit' => 25000,
                'dataState' => 'all',
            ]
        );

        $queries = [];

        foreach ($response['rows'] ?? [] as $row) {
            $query = trim((string) (($row['keys'][0] ?? '') ?: ''));

            if ($query === '') {
                continue;
            }

            $queries[] = [
                'query' => $query,
                'clicks' => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr' => round((float) ($row['ctr'] ?? 0), 4),
                'position' => round((float) ($row['position'] ?? 0), 2),
            ];
        }

        usort($queries, fn (array $a, array $b): int => $b['clicks'] <=> $a['clicks']);

        return $queries;
    }

    /**
     * @return array{clicks: int, impressions: int}
     */
    private function siteTotals(string $siteUrl, string $startDate, string $endDate): array
    {
        $response = $this->apiPost(
            '/webmasters/v3/sites/'.rawurlencode($siteUrl).'/searchAnalytics/query',
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dataState' => 'all',
            ]
        );

        $row = ($response['rows'] ?? [])[0] ?? null;

        if (! is_array($row)) {
            return ['clicks' => 0, 'impressions' => 0];
        }

        return [
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function apiPost(string $path, array $body): array
    {
        $response = Http::timeout(90)
            ->withToken($this->accessToken())
            ->acceptJson()
            ->post('https://www.googleapis.com'.$path, $body);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'GSC API hatasi (%s): %s',
                $response->status(),
                $response->json('error.message') ?? $response->body()
            ));
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function accessToken(): string
    {
        $credentials = new ServiceAccountCredentials(self::SCOPE, $this->credentialsPath());
        $token = $credentials->fetchAuthToken();
        $accessToken = $token['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('GSC access token alinamadi.');
        }

        return $accessToken;
    }

    private function credentialsPath(): string
    {
        $configured = (string) config('google_seo.credentials_path');
        $candidates = array_values(array_filter([
            $configured !== '' ? $configured : null,
            storage_path('app/private/gsc-service-account.json'),
        ]));

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'Google SEO credentials bulunamadi. Sunucuda storage/app/private/gsc-service-account.json dosyasini olusturun veya GOOGLE_SEO_CREDENTIALS ayarlayin.'
        );
    }

    private function resolveSiteUrl(): string
    {
        $site = (string) config('google_seo.gsc_site_url', 'https://kosarticaret.com/');

        if (! str_ends_with($site, '/')) {
            $site .= '/';
        }

        return $site;
    }
}
