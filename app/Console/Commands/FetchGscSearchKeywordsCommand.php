<?php

namespace App\Console\Commands;

use App\Services\Seo\GscQueryApiFetcher;
use App\Services\Seo\GscSearchKeywordsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FetchGscSearchKeywordsCommand extends Command
{
    protected $signature = 'seo:fetch-gsc-keywords
                            {--days=* : Tek donem (7, 28, 90). Bos birakilirsa uc donem de cekilir}';

    protected $description = 'Google Search Console arama kelimelerini panel onbellegine ceker (7/28/90 gun).';

    public function handle(GscQueryApiFetcher $fetcher, GscSearchKeywordsService $keywords): int
    {
        $periods = $this->resolvePeriods();

        foreach ($periods as $days) {
            $this->info('GSC sorgulari cekiliyor: son '.$days.' gun');

            try {
                $payload = $fetcher->fetch($days);
            } catch (\Throwable $e) {
                $this->error('Donem '.$days.' basarisiz: '.$e->getMessage());

                return self::FAILURE;
            }

            $cache = $keywords->buildCachePayload($payload);
            $path = $keywords->periodCachePath($days);

            File::ensureDirectoryExists(dirname($path));
            file_put_contents(
                $path,
                json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
            );

            $this->line(sprintf(
                '  · %s — %d sorgu, %d tiklama, %d gosterim',
                basename($path),
                (int) ($cache['totals']['query_count'] ?? 0),
                (int) ($cache['totals']['clicks'] ?? 0),
                (int) ($cache['totals']['impressions'] ?? 0),
            ));
        }

        $this->info('GSC kelime onbellegi guncellendi.');

        return self::SUCCESS;
    }

    /** @return list<int> */
    private function resolvePeriods(): array
    {
        $requested = collect($this->option('days'))
            ->flatMap(fn ($value) => explode(',', (string) $value))
            ->map(fn ($value) => (int) trim((string) $value))
            ->filter(fn (int $days) => in_array($days, GscSearchKeywordsService::LIVE_PERIODS, true))
            ->unique()
            ->values()
            ->all();

        return $requested !== [] ? $requested : GscSearchKeywordsService::LIVE_PERIODS;
    }
}
