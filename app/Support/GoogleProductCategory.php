<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

final class GoogleProductCategory
{
    /** Google ürün taksonomisi ID — https://www.google.com/basepages/producttype/taxonomy.tr-TR.txt */
    private const DEFAULT_ID = 1869;

    /** @var array<string, int> */
    private const SLUG_MAP = [
        'su-pompalari' => 1869,
        'hidrofor-sistemleri' => 1869,
        'vantilatorler' => 1795,
        'elektrik-ve-aydinlatma' => 127,
    ];

    public static function forProduct(Product $product): int
    {
        $primary = $product->relationLoaded('categories')
            ? $product->categories->first()
            : $product->categories()->orderBy('categories.id')->first();

        if ($primary instanceof Category) {
            return self::forCategory($primary);
        }

        return self::DEFAULT_ID;
    }

    public static function forCategory(Category $category): int
    {
        foreach ($category->ancestorsAndSelf() as $node) {
            if (isset(self::SLUG_MAP[$node->slug])) {
                return self::SLUG_MAP[$node->slug];
            }
        }

        return self::DEFAULT_ID;
    }
}
