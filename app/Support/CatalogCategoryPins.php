<?php

namespace App\Support;

use App\Models\Category;

class CatalogCategoryPins
{
    /**
     * @return list<string>
     */
    public static function skuOrderFor(Category $category): array
    {
        $map = config('catalog_category_pins', []);
        if (! is_array($map)) {
            return [];
        }

        $skus = $map[$category->slug] ?? null;
        if (! is_array($skus) || $skus === []) {
            return [];
        }

        $out = [];
        foreach ($skus as $sku) {
            if (! is_string($sku)) {
                continue;
            }
            $sku = trim($sku);
            if ($sku === '') {
                continue;
            }
            $out[] = $sku;
        }

        return array_values(array_unique($out));
    }

    public static function appliesToDefaultSort(Category $category, string $siralama): bool
    {
        if ($siralama !== '' && in_array($siralama, CatalogPaginationSeo::SORT_VALUES, true)) {
            return false;
        }

        return self::skuOrderFor($category) !== [];
    }
}
