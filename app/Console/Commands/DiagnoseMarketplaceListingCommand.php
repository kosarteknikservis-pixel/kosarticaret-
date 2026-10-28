<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceSyncLog;
use App\Models\Product;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;

class DiagnoseMarketplaceListingCommand extends Command
{
    protected $signature = 'marketplace:diagnose-listing
        {slug : Ürün slug}
        {--channel=trendyol}';

    protected $description = 'Listing kaydı, son sync log ve Trendyol batch/barkod durumunu gösterir.';

    public function handle(TrendyolApiClient $apiClient): int
    {
        $slug = (string) $this->argument('slug');
        $channelKey = (string) $this->option('channel');

        $product = Product::query()->with('brand')->where('slug', $slug)->first();
        if ($product === null) {
            $this->error('Ürün yok: '.$slug);

            return self::FAILURE;
        }

        $this->info('Ürün #'.$product->id.' | '.$product->name);
        $this->line('Marka: '.($product->brand?->name ?: '—').' | SKU: '.($product->sku ?: '—').' | Barkod: '.($product->barcode ?: '—'));

        $listing = MarketplaceListing::query()
            ->where('product_id', $product->id)
            ->where('channel_key', $channelKey)
            ->first();

        if ($listing === null) {
            $this->warn('Listing kaydı yok.');
        } else {
            $this->line('Listing status: '.$listing->status);
            $this->line('Listing channel_price: '.($listing->channel_price ?? '—'));
            $this->line('Listing external_product_id (batch?): '.($listing->external_product_id ?: '—'));
            $this->line('Listing last_error: '.($listing->last_error ?: '—'));
            $this->line('Listing last_synced_at: '.($listing->last_synced_at?->toDateTimeString() ?: '—'));
        }

        $logs = MarketplaceSyncLog::query()
            ->where('channel_key', $channelKey)
            ->where('product_id', $product->id)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'action', 'status', 'message', 'created_at', 'context']);

        $this->newLine();
        $this->info('Son sync loglar:');
        foreach ($logs as $log) {
            $this->line(sprintf(
                '#%d %s | %s | %s | %s',
                $log->id,
                $log->created_at,
                $log->action,
                $log->status,
                $log->message
            ));
            if (is_array($log->context) && $log->context !== []) {
                $this->line('  context: '.json_encode($log->context, JSON_UNESCAPED_UNICODE));
            }
        }

        $channel = MarketplaceChannel::query()->where('key', $channelKey)->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->warn('Kanal API bilgisi yok; Trendyol sorgusu atlandı.');

            return self::SUCCESS;
        }

        try {
            $client = $apiClient->forChannel($channel);

            if ($listing && filled($listing->external_product_id)) {
                $this->newLine();
                $this->info('Trendyol batch: '.$listing->external_product_id);
                try {
                    $batch = $client->getBatchRequest((string) $listing->external_product_id);
                    $this->line(json_encode($batch, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                } catch (\Throwable $e) {
                    $this->error('Batch sorgu hatası: '.$e->getMessage());
                }
            }

            if (filled($product->barcode)) {
                $this->newLine();
                $this->info('Trendyol ürün sorgusu barcode='.$product->barcode);
                try {
                    $products = $client->getProductsByBarcode((string) $product->barcode);
                    $this->line(json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                } catch (\Throwable $e) {
                    $this->error('Ürün sorgu hatası: '.$e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->error('API: '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}
