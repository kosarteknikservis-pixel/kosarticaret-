<?php

namespace App\Observers;

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Eloquent\Model;

class SitemapCacheObserver
{
    public function __construct(private UrlIndexingNotifier $indexing) {}

    public function saved(Model $model): void
    {
        $this->indexing->clearSitemapCache();
    }

    public function deleted(Model $model): void
    {
        $this->indexing->clearSitemapCache();
    }
}
