<?php

namespace App\Support;

use App\Models\Product;
use App\Services\StoreConfig;
use Illuminate\Support\Collection;

class ProductPurchaseOptions
{
    /**
     * @return list<array{
     *   key: string,
     *   label: string,
     *   slug: string,
     *   sku: string,
     *   product_id: int,
     *   price: float,
     *   price_formatted: string,
     *   compare_at_price: ?float,
     *   compare_at_formatted: ?string,
     *   havale_price: ?float,
     *   havale_formatted: ?string,
     *   in_stock: bool,
     *   stock: int,
     *   selected: bool,
     *   is_hub: bool
     * }>
     */
    public static function for(Product $product, ?string $selectedKey = null): array
    {
        try {
            $group = self::groupKey($product);
            if ($group === null) {
                return [];
            }

            $members = self::members($group);
            if ($members->count() < 2) {
                return [];
            }

            $storeConfig = app(StoreConfig::class);
            $availableKeys = $members->map(fn (Product $member) => self::optionKey($member))->filter()->values();
            if (! is_string($selectedKey) || $selectedKey === '' || ! $availableKeys->contains($selectedKey)) {
                $selectedKey = self::optionKey($product) ?? $availableKeys->first();
            }

            return $members->map(function (Product $member) use ($storeConfig, $selectedKey): array {
                $price = (float) $member->price;
                $compare = $member->compare_at_price !== null ? (float) $member->compare_at_price : null;
                $havale = $storeConfig->priceAfterHavale($price);
                $key = self::optionKey($member) ?? ('id-'.$member->id);

                return [
                    'key' => $key,
                    'label' => self::optionLabel($member),
                    'slug' => $member->slug,
                    'sku' => (string) ($member->sku ?? ''),
                    'product_id' => (int) $member->id,
                    'price' => $price,
                    'price_formatted' => number_format($price, 2, ',', '.').' ₺',
                    'compare_at_price' => $compare,
                    'compare_at_formatted' => $compare !== null
                        ? number_format($compare, 2, ',', '.').' ₺'
                        : null,
                    'havale_price' => $havale,
                    'havale_formatted' => $havale !== null
                        ? number_format($havale, 2, ',', '.').' ₺'
                        : null,
                    'in_stock' => $member->inStock(),
                    'stock' => (int) $member->stock,
                    'selected' => $selectedKey !== null && $selectedKey === $key,
                    'is_hub' => self::isHub($member),
                ];
            })->values()->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public static function groupKey(Product $product): ?string
    {
        $group = data_get($product->specs, 'purchase_group');

        return is_string($group) && $group !== '' ? $group : null;
    }

    public static function optionKey(Product $product): ?string
    {
        $key = data_get($product->specs, 'purchase_option_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    public static function optionLabel(Product $product): string
    {
        $label = data_get($product->specs, 'purchase_option_label');
        if (is_string($label) && $label !== '') {
            return $label;
        }

        return (string) ($product->sku ?: $product->name);
    }

    public static function isHub(Product $product): bool
    {
        return data_get($product->specs, 'purchase_option_role') === 'hub';
    }

    public static function isChild(Product $product): bool
    {
        return data_get($product->specs, 'purchase_option_role') === 'child';
    }

    public static function hubSlug(Product $product): ?string
    {
        $slug = data_get($product->specs, 'purchase_hub_slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /** @return Collection<int, Product> */
    public static function members(string $group): Collection
    {
        return Product::query()
            ->active()
            ->where('specs->purchase_group', $group)
            ->orderBy('id')
            ->get()
            ->sortBy(fn (Product $product) => sprintf(
                '%05d-%010d',
                (int) data_get($product->specs, 'purchase_option_sort', 999),
                (int) $product->id
            ))
            ->values();
    }

    /**
     * @return array{low: float, high: float}|null
     */
    public static function priceRange(Product $product): ?array
    {
        $options = self::for($product);
        if ($options === []) {
            return null;
        }

        $prices = array_column($options, 'price');

        return [
            'low' => min($prices),
            'high' => max($prices),
        ];
    }
}
