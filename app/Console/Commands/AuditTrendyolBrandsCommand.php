<?php

namespace App\Console\Commands;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;

class AuditTrendyolBrandsCommand extends Command
{
    protected $signature = 'marketplace:trendyol-brand-audit {--limit=12 : Kaç listing Trendyol API ile kontrol edilsin}';

    protected $description = 'Trendyol marka eşlemelerini ve canlı listing brandId alanını gösterir.';

    public function handle(TrendyolApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel === null) {
            $this->error('Trendyol kanalı yok.');

            return self::FAILURE;
        }

        $this->info('Kanal force_brand_external_id: '.(string) data_get($channel->settings, 'force_brand_external_id', '—'));
        $this->info('Kanal force_brand_external_name: '.(string) data_get($channel->settings, 'force_brand_external_name', '—'));

        $this->newLine();
        $this->info('Trendyol brand mapping (site markası → Trendyol):');
        $maps = MarketplaceBrandMapping::query()
            ->where('channel_key', 'trendyol')
            ->with('brand:id,name,slug')
            ->orderBy('brand_id')
            ->get();

        foreach ($maps as $map) {
            $this->line(sprintf(
                '- %s (#%d) → %s (#%s)%s',
                $map->brand?->name ?? '?',
                $map->brand_id,
                $map->external_brand_name ?: '—',
                $map->external_brand_id,
                data_get($map->metadata, 'forced_brand') ? ' [forced]' : ''
            ));
        }

        try {
            $client = $apiClient->forChannel($channel);
        } catch (\Throwable $e) {
            $this->error('API: '.$e->getMessage());

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $listings = MarketplaceListing::query()
            ->where('channel_key', 'trendyol')
            ->with(['product.brand'])
            ->orderByDesc('last_synced_at')
            ->limit($limit)
            ->get();

        $this->newLine();
        $this->info('Canlı Trendyol brandId örnekleri (limit='.$limit.'):');

        foreach ($listings as $listing) {
            $product = $listing->product;
            if ($product === null || blank($product->barcode)) {
                $this->warn('Listing #'.$listing->id.' barkodsuz, atlandı.');

                continue;
            }

            try {
                $raw = $client->getProductsByBarcode((string) $product->barcode);
                $row = data_get($raw, 'content.0')
                    ?? data_get($raw, 'products.0')
                    ?? (isset($raw['barcode']) ? $raw : null);

                $brandId = data_get($row, 'brandId')
                    ?? data_get($row, 'brand.id')
                    ?? data_get($raw, 'brandId')
                    ?? data_get($raw, 'brand.id');
                $brandName = data_get($row, 'brandName')
                    ?? data_get($row, 'brand.name')
                    ?? data_get($row, 'brand')
                    ?? data_get($raw, 'brandName')
                    ?? data_get($raw, 'brand.name');
                $approved = data_get($row, 'approved')
                    ?? data_get($row, 'onSale')
                    ?? data_get($row, 'productContentId')
                    ?? data_get($raw, 'approved');

                $this->line(sprintf(
                    '- %s | site=%s | TY brandId=%s name=%s | listing=%s | approved/onSale~=%s',
                    $product->sku ?: $product->slug,
                    $product->brand?->name ?? '—',
                    $brandId === null ? '—' : (string) $brandId,
                    is_scalar($brandName) ? (string) $brandName : json_encode($brandName, JSON_UNESCAPED_UNICODE),
                    $listing->status,
                    is_scalar($approved) ? (string) $approved : json_encode($approved, JSON_UNESCAPED_UNICODE)
                ));

                // İlk 2 üründe ham yanıtın marka ile ilgili alanlarını göster.
                static $dumps = 0;
                if ($dumps < 2) {
                    $dumps++;
                    $keys = is_array($row) ? array_keys($row) : (is_array($raw) ? array_keys($raw) : []);
                    $this->line('  raw_keys='.implode(',', $keys));
                    $subset = [];
                    foreach (['brandId', 'brandName', 'brand', 'title', 'productMainId', 'barcode', 'approved', 'archived', 'onSale', 'hasActiveCampaign', 'locked'] as $k) {
                        if (is_array($row) && array_key_exists($k, $row)) {
                            $subset[$k] = $row[$k];
                        } elseif (is_array($raw) && array_key_exists($k, $raw)) {
                            $subset[$k] = $raw[$k];
                        }
                    }
                    $this->line('  brand_subset='.json_encode($subset, JSON_UNESCAPED_UNICODE));
                    if (is_array($listing->payload_snapshot)) {
                        $sentBrand = data_get($listing->payload_snapshot, 'items.0.brandId')
                            ?? data_get($listing->payload_snapshot, '0.brandId');
                        $this->line('  last_sent_brandId='.json_encode($sentBrand, JSON_UNESCAPED_UNICODE));
                    }
                }

                if ($listing->external_product_id && ! str_contains((string) $listing->external_product_id, '-')) {
                    // batch id genelde UUID; kısa id değilse batch dene
                }
                if ($listing->external_product_id && str_contains((string) $listing->external_product_id, '-')) {
                    try {
                        $batch = $client->getBatchRequest((string) $listing->external_product_id);
                        $itemStatus = data_get($batch, 'items.0.status')
                            ?? data_get($batch, 'itemList.0.status')
                            ?? data_get($batch, 'status');
                        $itemFail = data_get($batch, 'items.0.failureReasons')
                            ?? data_get($batch, 'items.0.errors')
                            ?? data_get($batch, 'itemList.0.failureReasons');
                        $this->line('  batch='.$listing->external_product_id.' status='.json_encode($itemStatus, JSON_UNESCAPED_UNICODE)
                            .' fail='.json_encode($itemFail, JSON_UNESCAPED_UNICODE));
                    } catch (\Throwable $e) {
                        $this->line('  batch sorgu: '.$e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                $this->error('- '.$product->sku.': '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
