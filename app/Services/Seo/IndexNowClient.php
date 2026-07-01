<?php

namespace App\Services\Seo;

use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IndexNowClient
{
    /** @return array{ok: bool, status?: int, error?: string, skipped?: bool} */
    public function submit(array $urls): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => true, 'skipped' => true];
        }

        $urls = $this->normalizeUrls($urls);
        if ($urls === []) {
            return ['ok' => true, 'skipped' => true];
        }

        $key = $this->key();
        $host = parse_url(Seo::siteUrl(), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return ['ok' => false, 'error' => 'Geçersiz site host.'];
        }

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => Seo::absolute('/'.$key.'.txt'),
            'urlList' => $urls,
        ];

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->post((string) config('seo.indexing.indexnow_endpoint'), $payload);

            if ($response->successful() || $response->status() === 202) {
                return ['ok' => true, 'status' => $response->status()];
            }

            return [
                'ok' => false,
                'status' => $response->status(),
                'error' => Str::limit($response->body(), 500),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function isEnabled(): bool
    {
        return SiteSetting::get('indexnow_enabled', '1') === '1' && filled($this->key());
    }

    public function key(): ?string
    {
        $key = trim((string) SiteSetting::get('indexnow_key', ''));

        return $key !== '' ? $key : null;
    }

    /** @param  list<string>  $urls */
    private function normalizeUrls(array $urls): array
    {
        $siteHost = parse_url(Seo::siteUrl(), PHP_URL_HOST);

        return collect($urls)
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->map(fn (string $url) => str_starts_with($url, 'http') ? $url : Seo::absolute($url))
            ->unique()
            ->filter(function (string $url) use ($siteHost) {
                $host = parse_url($url, PHP_URL_HOST);

                return is_string($host) && $host === $siteHost;
            })
            ->take((int) config('seo.indexing.max_urls_per_batch', 100))
            ->values()
            ->all();
    }
}
