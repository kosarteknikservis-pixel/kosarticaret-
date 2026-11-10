<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SitemapCacheObserver
{
    public function __construct(private UrlIndexingNotifier $indexing) {}

    public function saved(Model $model): void
    {
        $this->indexing->clearSitemapCache();

        if ($model instanceof Category) {
            Cache::forget('category.slug_path_map');
        }
    }

    public function deleted(Model $model): void
    {
        $this->indexing->clearSitemapCache();

        if ($model instanceof Category) {
            Cache::forget('category.slug_path_map');
        }
    }
}
