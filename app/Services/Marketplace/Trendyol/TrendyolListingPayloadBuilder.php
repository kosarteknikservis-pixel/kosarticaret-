<?php

namespace App\Services\Marketplace\Trendyol;

use App\Models\MarketplaceAttributeMapping;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use InvalidArgumentException;

class TrendyolListingPayloadBuilder
{
    public function __construct(
        private MarketplaceChannelPricing $pricing,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>}
     */
    public function buildCreatePayload(Product $product, MarketplaceChannel $channel, ?MarketplaceListing $listing = null): array
    {
        $product->loadMissing(['brand', 'categories', 'images']);

        $categoryMapping = $this->resolveCategoryMapping($product, $channel->key);
        $brandMapping = $this->resolveBrandMapping($product, $channel->key);

        if ($categoryMapping === null) {
            throw new InvalidArgumentException('Kategori eşleştirmesi bulunamadı.');
        }

        if ($brandMapping === null) {
            throw new InvalidArgumentException('Marka eşleştirmesi bulunamadı.');
        }

        $images = $this->collectImages($product);

        if ($images === []) {
            throw new InvalidArgumentException('Trendyol için en az bir görsel URL gerekli.');
        }

        $description = trim(strip_tags((string) ($product->description ?: $product->short_description ?: $product->name)));

        if ($description === '') {
            throw new InvalidArgumentException('Ürün açıklaması boş.');
        }

        $salePrice = $this->pricing->salePrice($product, $channel, $listing);
        $listPrice = $this->pricing->listPrice($product, $channel, $listing);
        $stock = $this->pricing->stockQuantity($product, $channel, $listing);

        $item = [
            'barcode' => (string) $product->barcode,
            'title' => (string) $product->name,
            'productMainId' => (string) ($product->sku ?: $product->id),
            'brandId' => (int) $brandMapping->external_brand_id,
            'categoryId' => (int) $categoryMapping->external_category_id,
            'quantity' => $stock,
            'stockCode' => (string) $product->sku,
            'dimensionalWeight' => $product->desi() ?? 1,
            'description' => $description,
            'currencyType' => 'TRY',
            'listPrice' => $listPrice,
            'salePrice' => $salePrice,
            'vatRate' => (int) round($product->vatRateValue()),
            'images' => $images,
            'attributes' => $this->buildAttributes($product, $channel->key, (int) $categoryMapping->category_id),
        ];

        return ['items' => [$item]];
    }

    /**
     * @return array{items: list<array<string, mixed>>}
     */
    public function buildInventoryPayload(Product $product, MarketplaceChannel $channel, ?MarketplaceListing $listing = null): array
    {
        return [
            'items' => [[
                'barcode' => (string) $product->barcode,
                'quantity' => $this->pricing->stockQuantity($product, $channel, $listing),
                'salePrice' => $this->pricing->salePrice($product, $channel, $listing),
                'listPrice' => $this->pricing->listPrice($product, $channel, $listing),
            ]],
        ];
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
     * @return list<array{url: string}>
     */
    private function collectImages(Product $product): array
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

        return array_map(fn (string $url) => ['url' => $url], array_slice($urls, 0, 8));
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

    /**
     * @return list<array<string, mixed>>
     */
    private function buildAttributes(Product $product, string $channelKey, int $categoryId): array
    {
        $specs = $product->specs ?? [];

        if ($specs === []) {
            return [];
        }

        $mappings = MarketplaceAttributeMapping::query()
            ->where('channel_key', $channelKey)
            ->where('category_id', $categoryId)
            ->get()
            ->keyBy('local_spec_key');

        $attributes = [];

        foreach ($specs as $key => $value) {
            $mapping = $mappings->get((string) $key);

            if ($mapping === null || blank($value)) {
                continue;
            }

            $attribute = ['attributeId' => (int) $mapping->external_attribute_id];
            $mappedValue = $this->mapAttributeValue($mapping->value_map ?? [], (string) $value);

            if ($mappedValue !== null) {
                $attribute['attributeValueId'] = (int) $mappedValue;
            } else {
                $attribute['customAttributeValue'] = (string) $value;
            }

            $attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $valueMap
     */
    private function mapAttributeValue(array $valueMap, string $localValue): ?string
    {
        if ($valueMap === []) {
            return null;
        }

        foreach ($valueMap as $local => $external) {
            if (mb_strtolower(trim((string) $local)) === mb_strtolower(trim($localValue))) {
                return (string) $external;
            }
        }

        return null;
    }
}
