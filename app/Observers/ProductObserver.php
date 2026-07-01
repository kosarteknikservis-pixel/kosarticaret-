<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Seo\UrlIndexingNotifier;

class ProductObserver
{
    /** @var list<string> */
    private const INDEX_FIELDS = [
        'slug',
        'name',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
        'is_active',
        'price',
        'compare_at_price',
        'image',
        'brand_id',
    ];

    public function __construct(private UrlIndexingNotifier $indexing) {}

    public function saved(Product $product): void
    {
        if (! $product->is_active) {
            return;
        }

        if (! $product->wasRecentlyCreated && ! $product->wasChanged(self::INDEX_FIELDS)) {
            return;
        }

        $this->indexing->submit([
            route('products.show', $product, absolute: true),
        ]);
    }
}
