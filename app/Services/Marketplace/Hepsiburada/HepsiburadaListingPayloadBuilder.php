<?php

namespace App\Services\Marketplace\Hepsiburada;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use InvalidArgumentException;

class HepsiburadaListingPayloadBuilder
{
    public function __construct(
        private MarketplaceChannelPricing $pricing,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildImportPayload(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $priceOverride = null,
        ?HepsiburadaApiClient $client = null,
    ): array {
        $product->loadMissing(['brand', 'categories', 'images']);

        $categoryMapping = $this->resolveCategoryMapping($product, $channel->key);
        $brandMapping = $this->resolveBrandMapping($product, $channel->key);

        if ($categoryMapping === null) {
            throw new InvalidArgumentException('Hepsiburada kategori eşleştirmesi bulunamadı.');
        }

        if ($brandMapping === null) {
            throw new InvalidArgumentException('Hepsiburada marka eşleştirmesi bulunamadı.');
        }

        $images = $this->collectImageUrls($product);
        if ($images === []) {
            throw new InvalidArgumentException('Hepsiburada için en az bir görsel URL gerekli.');
        }

        $description = $this->sanitizeDescriptionHtml(
            (string) ($product->description ?: $product->short_description ?: $product->name)
        );
        if ($description === '') {
            throw new InvalidArgumentException('Ürün açıklaması boş.');
        }

        $salePrice = $this->pricing->salePrice($product, $channel, $listing, $priceOverride);
        $stock = $this->pricing->stockQuantity($product, $channel, $listing);
        $merchantSku = strtoupper((string) ($product->sku ?: 'P'.$product->id));
        $barcode = filled($product->barcode) ? (string) $product->barcode : $merchantSku;
        $brandName = (string) ($brandMapping->external_brand_name ?: $product->brand?->name ?: 'Diğer');
        $weight = $product->weight_kg !== null && (float) $product->weight_kg > 0
            ? (string) round((float) $product->weight_kg, 2)
            : (string) max(1, (int) ($product->desi() ?? 1));

        $attributes = [
            'merchantSku' => $merchantSku,
            'VaryantGroupID' => $merchantSku,
            'Barcode' => $barcode,
            'UrunAdi' => mb_substr((string) $product->name, 0, 200),
            'UrunAciklamasi' => $description,
            'Marka' => $brandName,
            'GarantiSuresi' => '24',
            'kg' => $weight,
            'tax_vat_rate' => (string) (int) round($product->vatRateValue()),
            'price' => $this->formatCatalogPrice($salePrice),
            'stock' => (string) max(0, $stock),
        ];

        foreach (array_values($images) as $index => $url) {
            if ($index >= 10) {
                break;
            }
            $attributes['Image'.($index + 1)] = $url;
        }

        if ($client !== null) {
            $attributes = $this->enrichRequiredAttributes(
                $attributes,
                $client,
                (int) $categoryMapping->external_category_id,
                $product,
            );
        }

        return [[
            'categoryId' => (int) $categoryMapping->external_category_id,
            'merchant' => trim((string) data_get($channel->credentials, 'merchant_id', '')),
            'attributes' => $attributes,
        ]];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function enrichRequiredAttributes(
        array $attributes,
        HepsiburadaApiClient $client,
        int $categoryId,
        Product $product,
    ): array {
        if ($categoryId <= 0) {
            return $attributes;
        }

        $catalog = $client->getCategoryAttributes($categoryId);
        $rows = $catalog['data'] ?? $catalog['attributes'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return $attributes;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = (string) ($row['name'] ?? $row['id'] ?? '');
            $mandatory = (bool) ($row['mandatory'] ?? $row['required'] ?? false);
            if ($name === '' || ! $mandatory) {
                continue;
            }

            if (array_key_exists($name, $attributes) && filled($attributes[$name])) {
                continue;
            }

            $value = $this->defaultValueForAttribute($name, $product, $attributes);
            if ($value === null || $value === '') {
                throw new InvalidArgumentException(
                    'Hepsiburada zorunlu özellik doldurulamadı: '.$name
                );
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function defaultValueForAttribute(string $name, Product $product, array $attributes): ?string
    {
        $normalized = mb_strtolower(trim($name));

        return match (true) {
            $normalized === 'marka' => (string) ($attributes['Marka'] ?? $product->brand?->name ?? ''),
            $normalized === 'urunadi' || $normalized === 'ürünadı' || str_contains($normalized, 'urunadi') => (string) $product->name,
            str_contains($normalized, 'aciklama') || str_contains($normalized, 'açıklama') => (string) ($attributes['UrunAciklamasi'] ?? $product->short_description ?? $product->name),
            str_contains($normalized, 'garanti') => '24',
            $normalized === 'kg' || str_contains($normalized, 'desi') => (string) ($attributes['kg'] ?? '1'),
            str_contains($normalized, 'mense') || str_contains($normalized, 'menşei') || str_contains($normalized, 'origin') => 'Türkiye',
            str_contains($normalized, 'renk') => 'Standart',
            default => null,
        };
    }

    private function formatCatalogPrice(float $price): string
    {
        return number_format($price, 2, ',', '');
    }

    private function sanitizeDescriptionHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#</?a\b[^>]*>#i', '', $html) ?? $html;
        $allowed = '<p><br><ul><ol><li><h2><h3><h4><strong><b><em><table><thead><tbody><tr><th><td>';
        $html = trim(strip_tags($html, $allowed));

        $blocked = [
            '/garanti\s*süresi[^.<\n]*/iu',
            '/üretici\s+garanti\s+şartlar[^.<\n]*/iu',
            '/iade\s+(koşul|hak|politika)[^.<\n]*/iu',
            '/www\.[^\s<]+/iu',
            '/https?:\/\/[^\s<]+/iu',
            '/\b0\d{3}\s?\d{3}\s?\d{2}\s?\d{2}\b/u',
            '/\b05\d{9}\b/u',
        ];
        foreach ($blocked as $pattern) {
            $html = preg_replace($pattern, '', $html) ?? $html;
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $html) ?? $html);
    }

    private function resolveCategoryMapping(Product $product, string $channelKey): ?MarketplaceCategoryMapping
    {
        $categoryIds = $product->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            return null;
        }

        return MarketplaceCategoryMapping::query()
            ->where('channel_key', $channelKey)
            ->whereIn('category_id', $categoryIds)
            ->first();
    }

    private function resolveBrandMapping(Product $product, string $channelKey): ?MarketplaceBrandMapping
    {
        if (! $product->brand_id) {
            return null;
        }

        return MarketplaceBrandMapping::query()
            ->where('channel_key', $channelKey)
            ->where('brand_id', $product->brand_id)
            ->first();
    }

    /**
     * @return list<string>
     */
    private function collectImageUrls(Product $product): array
    {
        $urls = [];
        $primary = $this->absoluteImageUrl($product->imageUrl('product-pdp') ?: $product->imageUrl());
        if ($primary) {
            $urls[] = $primary;
        }

        foreach ($product->images as $image) {
            $url = $this->absoluteImageUrl($image->url('product-pdp'));
            if ($url && ! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return array_slice($urls, 0, 10);
    }

    private function absoluteImageUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
