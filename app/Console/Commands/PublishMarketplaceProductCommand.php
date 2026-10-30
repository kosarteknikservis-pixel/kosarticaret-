<?php

namespace App\Console\Commands;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Models\Product;
use App\Services\Marketplace\Idefix\IdefixApiClient;
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
        {--map-brand : Eksik marka eşleştirmesini oluştur (Trendyol/Idefix: API, Hepsiburada/N11: marka adı)}
        {--price= : Kanal satış fiyatı (site fiyatını değiştirmez)}
        {--dry-run : API ürün gönderimi yapmadan doğrula}';

    protected $description = 'Ürünü pazaryeri kanalına gönderir (trendyol | hepsiburada | n11 | idefix).';

    public function handle(
        ProductListingService $listingService,
        MarketplaceChannelPricing $pricing,
        ProductReadinessChecker $readiness,
        TrendyolApiClient $apiClient,
        IdefixApiClient $idefixApiClient,
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

        if (in_array($channelKey, ['hepsiburada', 'n11', 'idefix'], true) && $this->isKosarBrand($product)) {
            $this->warn('Koşar markalı ürün '.$channelKey.' kanalına gönderilmez; atlandı: '.$slug);

            return self::SUCCESS;
        }

        if ($this->option('map-brand')) {
            try {
                if ($channelKey === 'trendyol') {
                    $this->ensureTrendyolBrandMapping($product, $channel, $apiClient, $readiness);
                } elseif ($channelKey === 'hepsiburada') {
                    $this->ensureHepsiburadaBrandMapping($product, $channel, $readiness);
                } elseif ($channelKey === 'n11') {
                    $this->ensureN11BrandMapping($product, $channel, $readiness);
                } elseif ($channelKey === 'idefix') {
                    $this->ensureIdefixBrandMapping($product, $channel, $idefixApiClient, $readiness);
                }
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

        $this->line('Kanal: '.$channelKey);
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

        if ($channelKey === 'hepsiburada' && is_array($result['listing']?->payload_snapshot ?? null)) {
            $attrs = data_get($result['listing']->payload_snapshot, '0.attributes', []);
            if (is_array($attrs)) {
                if (filled($attrs['Barcode'] ?? null)) {
                    $this->line('Barcode: '.(string) $attrs['Barcode']);
                }
                if (filled($attrs['stock'] ?? null)) {
                    $this->line('Stock: '.(string) $attrs['stock']);
                }
                for ($i = 1; $i <= 10; $i++) {
                    $img = $attrs['Image'.$i] ?? null;
                    if (is_string($img) && $img !== '') {
                        $this->line('Image'.$i.': '.$img);
                    }
                }
            }
        }

        if ($channelKey === 'n11' && is_array($result['listing']?->payload_snapshot ?? null)) {
            $sku = data_get($result['listing']->payload_snapshot, 'payload.skus.0', []);
            if (is_array($sku)) {
                $this->line('StockCode: '.(string) ($sku['stockCode'] ?? ''));
                $this->line('Stock: '.(string) ($sku['quantity'] ?? ''));
                $this->line('SalePrice: '.(string) ($sku['salePrice'] ?? ''));
                $this->line('ShipmentTemplate: '.(string) ($sku['shipmentTemplate'] ?? ''));
                $images = $sku['images'] ?? [];
                if (is_array($images)) {
                    foreach ($images as $i => $img) {
                        if (is_array($img) && filled($img['url'] ?? null)) {
                            $this->line('Image'.((int) $i + 1).': '.(string) $img['url']);
                        }
                    }
                }
            }
        }

        if ($channelKey === 'idefix' && is_array($result['listing']?->payload_snapshot ?? null)) {
            $row = data_get($result['listing']->payload_snapshot, 'products.0', []);
            if (is_array($row)) {
                $this->line('Barcode: '.(string) ($row['barcode'] ?? ''));
                $this->line('VendorStockCode: '.(string) ($row['vendorStockCode'] ?? ''));
                $this->line('Stock: '.(string) ($row['inventoryQuantity'] ?? ''));
                $this->line('Price: '.(string) ($row['price'] ?? ''));
                $this->line('CategoryId: '.(string) ($row['categoryId'] ?? ''));
                $this->line('BrandId: '.(string) ($row['brandId'] ?? ''));
                $images = $row['images'] ?? [];
                if (is_array($images)) {
                    foreach ($images as $i => $img) {
                        if (is_array($img) && filled($img['url'] ?? null)) {
                            $this->line('Image'.((int) $i + 1).': '.(string) $img['url']);
                        }
                    }
                }
            }
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    private function ensureTrendyolBrandMapping(
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

    private function isKosarBrand(Product $product): bool
    {
        $brand = $product->brand;
        if ($brand === null) {
            return false;
        }

        $slug = mb_strtolower(trim((string) ($brand->slug ?? '')), 'UTF-8');
        $name = mb_strtolower(trim((string) ($brand->name ?? '')), 'UTF-8');
        $ascii = strtr($name, ['ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c']);

        return $slug === 'kosar'
            || str_contains($slug, 'kosar')
            || str_contains($ascii, 'kosar');
    }

    private function ensureHepsiburadaBrandMapping(
        Product $product,
        MarketplaceChannel $channel,
        ProductReadinessChecker $readiness,
    ): void {
        if ($product->brand_id === null || $product->brand === null) {
            throw new InvalidArgumentException('Ürünün markası yok.');
        }

        if ($readiness->brandMapped($product, $channel->key)) {
            $this->line('Marka eşleştirmesi zaten var.');

            return;
        }

        $name = (string) $product->brand->name;

        MarketplaceBrandMapping::query()->updateOrCreate(
            [
                'channel_key' => $channel->key,
                'brand_id' => $product->brand_id,
            ],
            [
                'external_brand_id' => $name,
                'external_brand_name' => $name,
            ],
        );

        $this->info('Hepsiburada marka eşleştirildi: '.$name);
    }

    private function ensureN11BrandMapping(
        Product $product,
        MarketplaceChannel $channel,
        ProductReadinessChecker $readiness,
    ): void {
        if ($product->brand_id === null || $product->brand === null) {
            throw new InvalidArgumentException('Ürünün markası yok.');
        }

        if ($readiness->brandMapped($product, $channel->key)) {
            $this->line('Marka eşleştirmesi zaten var.');

            return;
        }

        $name = (string) $product->brand->name;

        MarketplaceBrandMapping::query()->updateOrCreate(
            [
                'channel_key' => $channel->key,
                'brand_id' => $product->brand_id,
            ],
            [
                'external_brand_id' => $name,
                'external_brand_name' => $name,
            ],
        );

        $this->info('N11 marka eşleştirildi: '.$name);
    }

    private function ensureIdefixBrandMapping(
        Product $product,
        MarketplaceChannel $channel,
        IdefixApiClient $apiClient,
        ProductReadinessChecker $readiness,
    ): void {
        if ($product->brand_id === null || $product->brand === null) {
            throw new InvalidArgumentException('Ürünün markası yok.');
        }

        $forceSumak = $this->isTroyBrand($product)
            || str_starts_with(mb_strtolower((string) $product->slug, 'UTF-8'), 'troy-');
        $isSumakBrand = mb_strtolower((string) ($product->brand->slug ?? ''), 'UTF-8') === 'sumak'
            || mb_strtolower((string) ($product->brand->name ?? ''), 'UTF-8') === 'sumak';

        // Canlı katalog Sumak: #20038760
        if ($forceSumak || $isSumakBrand) {
            $sumakIds = array_values(array_filter([
                $product->brand_id,
                (int) (\App\Models\Brand::query()->where('slug', 'sumak')->value('id') ?: 0),
            ]));
            foreach (array_unique($sumakIds) as $brandId) {
                MarketplaceBrandMapping::query()->updateOrCreate(
                    ['channel_key' => $channel->key, 'brand_id' => $brandId],
                    ['external_brand_id' => '20038760', 'external_brand_name' => 'Sumak'],
                );
            }
            $this->info('Idefix marka Sumak #20038760'.($forceSumak ? ' (Troy→Sumak)' : ''));

            return;
        }

        if ($readiness->brandMapped($product, $channel->key)) {
            $this->line('Marka eşleştirmesi zaten var.');

            return;
        }

        $client = $apiClient->forChannel($channel);
        $name = (string) $product->brand->name;
        $match = null;
        foreach (array_unique([$name, mb_strtoupper($name), mb_strtolower($name), ucfirst(mb_strtolower($name))]) as $variant) {
            try {
                $row = $client->searchBrandByName($variant);
            } catch (\Throwable) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $title = (string) ($row['title'] ?? '');
            if ($id > 0) {
                $match = ['id' => $id, 'title' => $title !== '' ? $title : $name];
                break;
            }
        }

        if ($match === null) {
            throw new InvalidArgumentException('Idefix’te “'.$name.'” markası bulunamadı.');
        }

        // Troy ürün: hem troy hem sumak brand_id eşlemesini Sumak’a yaz.
        $brandIds = [$product->brand_id];
        if ($forceSumak) {
            $sumakId = \App\Models\Brand::query()->where('slug', 'sumak')->value('id');
            if ($sumakId) {
                $brandIds[] = (int) $sumakId;
            }
        }

        foreach (array_unique($brandIds) as $brandId) {
            MarketplaceBrandMapping::query()->updateOrCreate(
                [
                    'channel_key' => $channel->key,
                    'brand_id' => $brandId,
                ],
                [
                    'external_brand_id' => (string) $match['id'],
                    'external_brand_name' => $match['title'],
                ],
            );
        }

        $this->info(
            ($forceSumak ? 'Idefix marka (Troy→Sumak): ' : 'Idefix marka eşleştirildi: ')
            .$match['title'].' (#'.$match['id'].')'
        );
    }

    private function isTroyBrand(Product $product): bool
    {
        $brand = $product->brand;
        if ($brand === null) {
            return false;
        }

        $slug = mb_strtolower(trim((string) ($brand->slug ?? '')), 'UTF-8');
        $name = mb_strtolower(trim((string) ($brand->name ?? '')), 'UTF-8');

        return $slug === 'troy' || $name === 'troy';
    }
}
