<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CollectionMatcher;
use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Support\Facades\Schema;

class ProductObserver
{
    private static ?bool $collectionsReady = null;

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
        $this->syncCollections($product);

        $requiresSitemapRefresh = $product->wasRecentlyCreated
            || $product->wasChanged(self::INDEX_FIELDS);

        if ($requiresSitemapRefresh) {
            $this->indexing->clearSitemapCache();
        }

        if (! $product->is_active) {
            return;
        }

        if (! $requiresSitemapRefresh) {
            return;
        }

        $this->indexing->submit([
            route('products.show', $product, absolute: true),
        ]);
    }

    private function syncCollections(Product $product): void
    {
        if (self::$collectionsReady !== true) {
            if (! Schema::hasTable('collection_products')) {
                return;
            }
            self::$collectionsReady = true;
        }

        if (! $product->wasRecentlyCreated && ! $product->wasChanged(['specs', 'is_active', 'brand_id'])) {
            return;
        }

        app(CollectionMatcher::class)->syncProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->indexing->clearSitemapCache();
    }
}
