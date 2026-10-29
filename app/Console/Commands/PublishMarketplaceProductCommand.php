<?php

namespace App\Console\Commands;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use App\Services\Marketplace\ProductListingService;
use App\Services\Marketplace\ProductReadinessChecker;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PublishMarketplaceProductCommand extends Command
{
    protected $signature = 'marketplace:publish-product
        {slug : Ürün slug}
        {--channel=trendyol : Kanal anahtarı}
        {--map-brand : Eksik Trendyol marka eşleştirmesini API ile oluştur}
        {--price= : Trendyol satış fiyatı (site fiyatını değiştirmez)}
        {--dry-run : API ürün gönderimi yapmadan doğrula}';

    protected $description = 'Ürünü pazaryeri kanalına gönderir (varsayılan Trendyol).';

    public function handle(
        ProductListingService $listingService,
        MarketplaceChannelPricing $pricing,
        ProductReadinessChecker $readiness,
        TrendyolApiClient $apiClient,
    ): int {
        $slug = (string) $this->argument('slug');
        $channelKey = (string) $this->option('channel');

        $product = Product::query()->with(['brand', 'categories', 'images'])->where('slug', $slug)->first();
        if ($product === null) {
            $this->error('Ürün bulunamadı: '.$slug);

            return self::FAILURE;
        }

        $channel = MarketplaceChannel::query()->where('key', $channelKey)->first();
        if ($channel === null) {
            $this->error('Kanal bulunamadı: '.$channelKey);

            return self::FAILURE;
        }

        if ($this->option('map-brand') && $channelKey === 'trendyol') {
            try {
                $this->ensureBrandMapping($product, $channel, $apiClient, $readiness);
            } catch (\Throwable $e) {
                $this->error('Marka eşleştirme: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $priceOverride = $this->option('price') !== null && $this->option('price') !== ''
            ? (float) $this->option('price')
            : null;
        if ($priceOverride !== null && $priceOverride <= 0) {
            $this->error('--price pozitif bir tutar olmalı.');

            return self::FAILURE;
        }

        $sale = $pricing->salePrice($product, $channel, null, $priceOverride);
        $breakdown = $channel->setting('price_mode') === 'margin' && $priceOverride === null
            ? $pricing->marginBreakdown($product, $channel)
            : null;

        $this->line('Ürün: '.$product->name.' (#'.$product->id.')');
        $this->line('SKU: '.($product->sku ?: '—').' | Barkod: '.($product->barcode ?: '—'));
        $this->line('Maliyet: '.(($product->cost_price !== null) ? number_format((float) $product->cost_price, 2, ',', '.').' ₺' : 'yok'));
        $this->line('Kanal fiyat modu: '.(string) $channel->setting('price_mode', 'site').($priceOverride !== null ? ' (override)' : ''));
        $this->line('Gönderilecek satış fiyatı: '.number_format($sale, 2, ',', '.').' ₺');

        if (is_array($breakdown)) {
            $this->line(sprintf(
                'Margin: komisyon %s%% + diğer %s%% + hedef kâr %s%% → tahmini net %s ₺',
                $breakdown['commission_percent'],
                $breakdown['other_fee_percent'],
                $breakdown['target_margin_percent'],
                number_format((float) ($breakdown['estimated_profit'] ?? 0), 2, ',', '.')
            ));
        }

        $validation = $listingService->validateForPublish($product->fresh(['brand', 'categories', 'images']), $channel);
        if (! $validation['ok']) {
            $this->error('Gönderim engellendi:');
            foreach ($validation['reasons'] as $reason) {
                $this->line(' - '.$reason);
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run: doğrulama geçti, ürün API çağrısı yapılmadı.');

            return self::SUCCESS;
        }

        try {
            $result = $listingService->publish($product->id, $channelKey, $priceOverride);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->{$result['success'] ? 'info' : 'error'}($result['message']);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    private function ensureBrandMapping(
        Product $product,
        MarketplaceChannel $channel,
        TrendyolApiClient $apiClient,
        ProductReadinessChecker $readiness,
    ): void {
        if ($product->brand_id === null || $product->brand === null) {
            throw new InvalidArgumentException('Ürünün markası yok.');
        }

        if ($readiness->brandMapped($product, $channel->key)) {
            $this->line('Marka eşleştirmesi zaten var.');

            return;
        }

        $client = $apiClient->forChannel($channel);
        $name = (string) $product->brand->name;
        $matches = $client->searchBrandsByName($name);

        if ($matches === []) {
            foreach (array_unique([$name, mb_strtoupper($name), mb_strtolower($name)]) as $variant) {
                $matches = $client->searchBrandsByName($variant);
                if ($matches !== []) {
                    break;
                }
            }
        }

        if ($matches === []) {
            throw new InvalidArgumentException('Trendyol’da “'.$name.'” markası bulunamadı.');
        }

        $exact = null;
        foreach ($matches as $row) {
            if (mb_strtolower(trim((string) ($row['name'] ?? ''))) === mb_strtolower($name)) {
                $exact = $row;
                break;
            }
        }
        $chosen = $exact ?? $matches[0];
        $externalId = (string) ($chosen['id'] ?? '');
        $externalName = (string) ($chosen['name'] ?? $name);

        if ($externalId === '') {
            throw new InvalidArgumentException('Trendyol marka yanıtında id yok.');
        }

        MarketplaceBrandMapping::query()->updateOrCreate(
            [
                'channel_key' => $channel->key,
                'brand_id' => $product->brand_id,
            ],
            [
                'external_brand_id' => $externalId,
                'external_brand_name' => $externalName,
            ],
        );

        $this->info('Marka eşleştirildi: '.$externalName.' (#'.$externalId.')');
    }
}
