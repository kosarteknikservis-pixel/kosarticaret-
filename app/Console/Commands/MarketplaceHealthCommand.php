<?php

namespace App\Console\Commands;

use App\Models\MarketplaceListing;
use App\Services\Marketplace\MarketplaceManager;
use App\Services\Marketplace\ProductReadinessChecker;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use App\Services\Marketplace\Trendyol\TrendyolApiException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class MarketplaceHealthCommand extends Command
{
    protected $signature = 'marketplace:health';

    protected $description = 'Pazaryeri kanalları ve katalog hazırlık durumunu özetler.';

    public function handle(
        MarketplaceManager $manager,
        ProductReadinessChecker $readiness,
        TrendyolApiClient $apiClient,
    ): int {
        if (! Schema::hasTable('marketplace_channels')) {
            $this->error('Pazaryeri tabloları henüz kurulmamış. php artisan migrate çalıştırın.');

            return self::FAILURE;
        }

        $this->info('Pazaryeri sağlık özeti');
        $this->newLine();

        foreach ($manager->channels() as $channel) {
            $configured = $channel->isConfigured() ? 'yapılandırıldı' : 'eksik';
            $active = $channel->is_active ? 'aktif' : 'pasif';
            $listings = MarketplaceListing::query()->where('channel_key', $channel->key)->count();
            $published = MarketplaceListing::query()->where('channel_key', $channel->key)->where('status', 'published')->count();

            $this->line(sprintf(
                '- %s [%s] · %s · credential: %s · listing: %d (yayında %d)',
                $channel->name,
                $channel->key,
                $active,
                $configured,
                $listings,
                $published,
            ));

            if ($channel->key === 'trendyol' && $channel->is_active && $channel->isConfigured()) {
                try {
                    $apiClient->forChannel($channel)->ping();
                    $this->info('  Trendyol API: erişilebilir');
                } catch (TrendyolApiException $e) {
                    $this->warn('  Trendyol API: '.$e->getMessage());
                }
            }

            if ($channel->last_error) {
                $this->warn('  Son hata: '.$channel->last_error);
            }
        }

        $sample = \App\Models\Product::query()->where('is_active', true)->limit(200)->get();
        $summary = $readiness->summarize($sample);

        $this->newLine();
        $this->info('Aktif ürün hazırlık (örnek '.$sample->count().'):');
        $this->line('  Hazır: '.$summary['ready']);
        $this->line('  Eksik: '.$summary['not_ready']);

        return self::SUCCESS;
    }
}
