<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\Seo\UrlIndexingNotifier;

class ProductImageObserver
{
    public function __construct(private UrlIndexingNotifier $indexing) {}

    public function saved(ProductImage $image): void
    {
        $this->indexing->clearSitemapCache();
    }

    public function deleted(ProductImage $image): void
    {
        $this->indexing->clearSitemapCache();
    }
}
