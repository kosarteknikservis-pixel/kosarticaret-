<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\PttAvm\PttAvmApiClient;
use Illuminate\Console\Command;

class InspectPttAvmTrackingCommand extends Command
{
    protected $signature = 'marketplace:pttavm-tracking
        {trackingId? : PTT trackingId}
        {--listing= : marketplace_listings.id}';

    protected $description = 'PTT AVM ürün upsert tracking sonucunu sorgular.';

    public function handle(PttAvmApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'pttavm')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('pttavm kanalı yapılandırılmamış.');

            return self::FAILURE;
        }

        $trackingId = trim((string) ($this->argument('trackingId') ?: ''));
        $listingId = (int) ($this->option('listing') ?: 0);
        if ($trackingId === '' && $listingId > 0) {
            $listing = MarketplaceListing::query()->find($listingId);
            $trackingId = trim((string) ($listing?->external_product_id ?: ''));
        }
        if ($trackingId === '') {
            $this->error('trackingId veya --listing gerekli.');

            return self::FAILURE;
        }

        try {
            $result = $apiClient->forChannel($channel)->trackingResult($trackingId);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('trackingId: '.$trackingId);
        $this->line('status: '.(string) data_get($result, 'status', data_get($result, 'Status', '—')));
        $this->line('progress: '.(string) data_get($result, 'progress', data_get($result, 'Progress', '—')));
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '');

        return self::SUCCESS;
    }
}
