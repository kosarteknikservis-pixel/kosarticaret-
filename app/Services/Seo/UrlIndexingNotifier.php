<?php

namespace App\Services\Seo;

use App\Jobs\SubmitUrlsForIndexingJob;
use App\Support\SitemapGenerator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UrlIndexingNotifier
{
    public function __construct(
        private IndexNowClient $indexNow,
        private GoogleIndexingClient $googleIndexing,
    ) {}

    /** @param  list<string>  $urls */
    public function submit(array $urls, bool $queue = true): void
    {
        $urls = collect($urls)
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->unique()
            ->values()
            ->all();

        if ($urls === []) {
            return;
        }

        if (! $this->isActive()) {
            return;
        }

        $this->clearSitemapCache();

        if ($queue && config('seo.indexing.queue', true)) {
            SubmitUrlsForIndexingJob::dispatch($urls);

            return;
        }

        $this->submitNow($urls);
    }

    /** @param  list<string>  $urls */
    public function submitNow(array $urls): void
    {
        if ($urls === []) {
            return;
        }

        $indexNowResult = $this->indexNow->submit($urls);
        if (! ($indexNowResult['ok'] ?? false) && ! ($indexNowResult['skipped'] ?? false)) {
            Log::warning('IndexNow gönderimi başarısız.', $indexNowResult);
        }

        if (! $this->googleIndexing->isEnabled()) {
            return;
        }

        foreach ($urls as $url) {
            $googleResult = $this->googleIndexing->submit($url);
            if (! ($googleResult['ok'] ?? false) && ! ($googleResult['skipped'] ?? false)) {
                Log::warning('Google Indexing gönderimi başarısız.', [
                    'url' => $url,
                    ...$googleResult,
                ]);
            }
        }
    }

    public function isActive(): bool
    {
        return $this->indexNow->isEnabled() || $this->googleIndexing->isEnabled();
    }

    public function clearSitemapCache(): void
    {
        Cache::forget('seo.sitemap.xml');

        foreach (['static', 'categories', 'brands', 'blog', 'pages'] as $chunk) {
            Cache::forget('seo.sitemap.chunk.'.$chunk);
        }

        $productPages = (int) ceil(max(1, SitemapGenerator::productCount()) / SitemapGenerator::CHUNK_SIZE);
        for ($page = 1; $page <= $productPages; $page++) {
            Cache::forget('seo.sitemap.chunk.products-'.$page);
        }
    }
}
