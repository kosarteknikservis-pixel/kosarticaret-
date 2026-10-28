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
     * Trendyol zorunlu kategori özelliklerini (garanti, menşei, tip vb.) API kataloğundan doldurur.
     *
     * @param  array{items: list<array<string, mixed>>}  $payload
     * @param  array<int, list<string>>  $preferredValues  attributeId => aday değerler
     * @return array{items: list<array<string, mixed>>}
     */
    public function enrichRequiredAttributes(array $payload, TrendyolApiClient $client, array $preferredValues = []): array
    {
        $item = $payload['items'][0] ?? null;
        if (! is_array($item)) {
            return $payload;
        }

        $categoryId = (int) ($item['categoryId'] ?? 0);
        if ($categoryId <= 0) {
            return $payload;
        }

        $catalog = $client->getCategoryAttributes($categoryId);
        $categoryAttributes = $catalog['categoryAttributes'] ?? $catalog['attributes'] ?? [];
        if (! is_array($categoryAttributes) || $categoryAttributes === []) {
            return $payload;
        }

        $defaults = $preferredValues + [
            433 => ['2 Yıl', '2 yıl', '2 sene', '24 Ay', '24 ay', '2'],
            1192 => ['Türkiye', 'TR', 'Turkiye', 'Turkey'],
            34 => ['Hidrofor', 'Paket Hidrofor', 'Su Hidroforu', 'Ev Tipi Hidrofor'],
        ];

        /** @var array<int, array<string, mixed>> $existingById */
        $existingById = [];
        foreach ($item['attributes'] ?? [] as $attribute) {
            if (is_array($attribute) && isset($attribute['attributeId'])) {
                $existingById[(int) $attribute['attributeId']] = $attribute;
            }
        }

        foreach ($categoryAttributes as $row) {
            if (! is_array($row)) {
                continue;
            }

            $attribute = $row['attribute'] ?? $row;
            $attributeId = (int) ($attribute['id'] ?? $row['attributeId'] ?? 0);
            $attributeName = (string) ($attribute['name'] ?? $row['attributeName'] ?? '');
            // Zorunlu alan veya bilinen eksik ID'ler (garanti / menşei / tip).
            $required = (bool) ($row['required'] ?? false)
                || (bool) ($attribute['required'] ?? false)
                || isset($defaults[$attributeId]);

            if ($attributeId <= 0 || ! $required || isset($existingById[$attributeId])) {
                continue;
            }

            $candidates = $defaults[$attributeId] ?? $this->defaultCandidatesForAttributeName($attributeName);
            $values = $row['attributeValues'] ?? $attribute['attributeValues'] ?? $row['values'] ?? [];
            $matched = $this->matchAttributeValue($values, $candidates);

            if ($matched === null) {
                continue;
            }

            $existingById[$attributeId] = [
                'attributeId' => $attributeId,
                'attributeValueId' => (int) $matched,
            ];
        }

        $item['attributes'] = array_values($existingById);
        $payload['items'][0] = $item;

        return $payload;
    }

    /** @param  list<string>  $candidates */
    private function defaultCandidatesForAttributeName(string $name): array
    {
        $normalized = mb_strtolower(trim($name));

        return match (true) {
            str_contains($normalized, 'garanti') => ['2 Yıl', '2 yıl', '24 Ay', '24 ay'],
            str_contains($normalized, 'menşei') || str_contains($normalized, 'mensei') || str_contains($normalized, 'origin') => ['Türkiye', 'TR'],
            $normalized === 'tip' || str_contains($normalized, 'ürün tipi') => ['Hidrofor', 'Paket Hidrofor'],
            default => [],
        };
    }

    /**
     * @param  mixed  $values
     * @param  list<string>  $candidates
     */
    private function matchAttributeValue(mixed $values, array $candidates): ?int
    {
        if (! is_array($values) || $values === [] || $candidates === []) {
            return null;
        }

        $normalizedCandidates = array_map(fn (string $v) => mb_strtolower(trim($v)), $candidates);

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }
            $label = mb_strtolower(trim((string) ($value['name'] ?? $value['value'] ?? '')));
            $id = (int) ($value['id'] ?? 0);
            if ($id > 0 && in_array($label, $normalizedCandidates, true)) {
                return $id;
            }
        }

        // Kısmi eşleşme: "2 Yıl" ⊂ "2 Yıl (24 Ay)" gibi
        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }
            $label = mb_strtolower(trim((string) ($value['name'] ?? $value['value'] ?? '')));
            $id = (int) ($value['id'] ?? 0);
            if ($id <= 0 || $label === '') {
                continue;
            }
            foreach ($normalizedCandidates as $candidate) {
                if ($candidate !== '' && (str_contains($label, $candidate) || str_contains($candidate, $label))) {
                    return $id;
                }
            }
        }

        return null;
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
