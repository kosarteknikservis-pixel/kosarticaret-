<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CollectionMatcher;
use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Support\Facades\Schema;

class ProductObserver
{
    private static ?bool $collectionsReady = null;

    private static ?bool $contentUpdatedAtReady = null;

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
            // wasRecentlyCreated aynı instance'ta sonraki update'lerde de true kalır;
            // content_updated_at yalnız gerçek INDEX_FIELDS değişiminde veya ilk kayıtta yazılır.
            if (self::contentUpdatedAtReady()
                && ($product->wasChanged(self::INDEX_FIELDS) || $product->content_updated_at === null)
            ) {
                $product->content_updated_at = now();
                $product->saveQuietly();
            }
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

    private static function contentUpdatedAtReady(): bool
    {
        if (self::$contentUpdatedAtReady === true) {
            return true;
        }

        // false cache'lenmez: migrate sırasında kolon sonradan eklenebilir
        return self::$contentUpdatedAtReady = Schema::hasColumn('products', 'content_updated_at');
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
