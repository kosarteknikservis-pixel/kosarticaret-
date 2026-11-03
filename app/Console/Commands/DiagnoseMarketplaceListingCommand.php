<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceSyncLog;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseMarketplaceListingCommand extends Command
{
    protected $signature = 'marketplace:diagnose-listing
        {slug : Ürün slug}
        {--channel=trendyol}';

    protected $description = 'Listing kaydı, son sync log ve Trendyol batch/barkod durumunu gösterir.';

    public function handle(TrendyolApiClient $apiClient, MarketplaceChannelPricing $pricing): int
    {
        $slug = (string) $this->argument('slug');
        $channelKey = (string) $this->option('channel');

        $product = Product::query()->with('brand')->where('slug', $slug)->first();
        if ($product === null) {
            $this->error('Ürün yok: '.$slug);

            return self::FAILURE;
        }

        $channel = MarketplaceChannel::query()->where('key', $channelKey)->first();

        $this->info('=== KANIT: ÜRÜN / KANAL ===');
        $this->info('Ürün #'.$product->id.' | '.$product->name);
        $this->line('Marka: '.($product->brand?->name ?: '—').' | SKU: '.($product->sku ?: '—').' | Barkod: '.($product->barcode ?: '—'));
        $this->line('Site price: '.number_format((float) $product->price, 2, '.', ''));
        $this->line('Kanal price_mode: '.($channel ? (string) $channel->setting('price_mode', 'site') : 'kanal yok'));
        $this->line('Kanal auto_sync_price: '.json_encode($channel?->setting('auto_sync_price')));
        $this->line('Kanal force_brand_id: '.json_encode($channel?->setting('force_brand_external_id')));

        $listing = MarketplaceListing::query()
            ->where('product_id', $product->id)
            ->where('channel_key', $channelKey)
            ->first();

        $this->newLine();
        $this->info('=== KANIT: LISTING ===');
        if ($listing === null) {
            $this->warn('Listing kaydı yok.');
        } else {
            $this->line('Listing status: '.$listing->status);
            $this->line('Listing channel_price: '.($listing->channel_price ?? '—'));
            $this->line('Listing external_sku: '.($listing->external_sku ?: '—'));
            $this->line('Listing external_barcode: '.($listing->external_barcode ?: '—'));
            $this->line('Listing external_product_id: '.($listing->external_product_id ?: '—'));
            $this->line('Listing last_error: '.($listing->last_error ?: '—'));
            $this->line('Listing last_synced_at: '.($listing->last_synced_at?->toDateTimeString() ?: '—'));
            $this->line('Computed salePrice(now): '.$pricing->salePrice($product, $channel, $listing, null));

            $snap = $listing->payload_snapshot;
            if (is_array($snap)) {
                $sale = data_get($snap, 'items.0.salePrice')
                    ?? data_get($snap, 'products.0.salePrice')
                    ?? data_get($snap, 'salePrice');
                $qty = data_get($snap, 'items.0.quantity')
                    ?? data_get($snap, 'products.0.quantity');
                $this->line('Son payload salePrice: '.json_encode($sale));
                $this->line('Son payload quantity: '.json_encode($qty));
                $this->line('Son payload (kisa): '.substr(json_encode($snap, JSON_UNESCAPED_UNICODE), 0, 800));
            } else {
                $this->line('payload_snapshot: yok');
            }
        }

        $this->newLine();
        $this->info('=== KANIT: BU ÜRÜN SYNC LOGLARI (son 15) ===');
        $logs = MarketplaceSyncLog::query()
            ->where('channel_key', $channelKey)
            ->where('product_id', $product->id)
            ->latest('id')
            ->limit(15)
            ->get(['id', 'action', 'status', 'message', 'created_at', 'context']);

        if ($logs->isEmpty()) {
            $this->warn('Bu ürün için sync log yok.');
        }
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
                $sale = data_get($log->context, 'response') ? 'response-var' : '';
                $payloadSale = data_get($log->context, 'payload.items.0.salePrice')
                    ?? data_get($log->context, 'request.items.0.salePrice');
                $batch = data_get($log->context, 'batchRequestId')
                    ?? data_get($log->context, 'response.batchRequestId');
                $this->line('  batch='.json_encode($batch).' payloadSale='.json_encode($payloadSale).' '.$sale);
                $this->line('  context: '.substr(json_encode($log->context, JSON_UNESCAPED_UNICODE), 0, 1000));
            }
        }

        $this->newLine();
        $this->info('=== KANIT: TÜM TRENDYOL inventory/publish LOGLARI (son 20, herhangi ürün) ===');
        $recent = MarketplaceSyncLog::query()
            ->where('channel_key', 'trendyol')
            ->whereIn('action', ['listing_inventory_update', 'listing_publish', 'listing_unapproved_update'])
            ->latest('id')
            ->limit(20)
            ->get(['id', 'product_id', 'action', 'status', 'message', 'created_at']);
        foreach ($recent as $log) {
            $this->line(sprintf(
                '#%d product=%s %s | %s | %s | %s',
                $log->id,
                $log->product_id,
                $log->created_at,
                $log->action,
                $log->status,
                mb_substr((string) $log->message, 0, 80)
            ));
        }

        $this->newLine();
        $this->info('=== KANIT: QUEUE / JOBS ===');
        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->count();
            $this->line('jobs bekleyen: '.$pending);
            $samples = DB::table('jobs')->orderByDesc('id')->limit(5)->get(['id', 'queue', 'payload', 'available_at', 'created_at']);
            foreach ($samples as $job) {
                $payload = (string) $job->payload;
                $isPub = str_contains($payload, 'PublishTrendyol') || str_contains($payload, 'PublishMarketplace');
                $hasR3001 = str_contains($payload, 'R-3001') || str_contains($payload, (string) $product->id);
                $this->line(sprintf(
                    'job#%s created=%s pub=%s r3001=%s',
                    $job->id,
                    $job->created_at,
                    $isPub ? 'yes' : 'no',
                    $hasR3001 ? 'yes' : 'no'
                ));
            }
        } else {
            $this->line('jobs tablosu yok');
        }
        if (Schema::hasTable('failed_jobs')) {
            $this->line('failed_jobs: '.DB::table('failed_jobs')->count());
            $failed = DB::table('failed_jobs')->orderByDesc('id')->limit(5)->get(['id', 'failed_at', 'payload']);
            foreach ($failed as $job) {
                $payload = (string) $job->payload;
                $this->line(sprintf(
                    'failed#%s at=%s trendyolPub=%s',
                    $job->id,
                    $job->failed_at,
                    (str_contains($payload, 'PublishTrendyol') || str_contains($payload, (string) $product->id)) ? 'maybe' : 'no'
                ));
            }
        }

        if ($channel === null || ! $channel->isConfigured()) {
            $this->warn('Kanal API bilgisi yok; Trendyol sorgusu atlandı.');

            return self::SUCCESS;
        }

        try {
            $client = $apiClient->forChannel($channel);

            if ($listing && filled($listing->external_product_id) && ! str_contains((string) $listing->external_product_id, '-')) {
                // numeric id — not a batch uuid
            } elseif ($listing && filled($listing->external_product_id)) {
                $this->newLine();
                $this->info('=== KANIT: TRENDYOL BATCH '.$listing->external_product_id.' ===');
                try {
                    $batch = $client->getBatchRequest((string) $listing->external_product_id);
                    $this->line(json_encode($batch, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                } catch (\Throwable $e) {
                    $this->error('Batch sorgu hatası: '.$e->getMessage());
                }
            }

            // Also probe batch from latest log context
            $latestBatch = null;
            foreach ($logs as $log) {
                $latestBatch = data_get($log->context, 'batchRequestId')
                    ?? data_get($log->context, 'response.batchRequestId');
                if (filled($latestBatch)) {
                    break;
                }
            }
            if (filled($latestBatch)) {
                $this->newLine();
                $this->info('=== KANIT: LOG BATCH '.$latestBatch.' ===');
                try {
                    $batch = $client->getBatchRequest((string) $latestBatch);
                    $this->line(json_encode($batch, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                } catch (\Throwable $e) {
                    $this->error('Log batch sorgu hatası: '.$e->getMessage());
                }
            }

            if (filled($product->barcode)) {
                $this->newLine();
                $this->info('=== KANIT: TRENDYOL API barcode='.$product->barcode.' ===');
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

        $this->newLine();
        $this->info('=== KANIT: SCHEDULE (kod) ===');
        $this->line('marketplace:import-trendyol-orders everyTenMinutes — fiyat güncellemez (sipariş).');
        $this->line('queue:work everyMinute — kuyruktaki PublishTrendyolListingJob varsa fiyat gönderir.');
        $this->line('auto_sync_price UI Faz4 — kodda sync job yok.');

        return self::SUCCESS;
    }
};
