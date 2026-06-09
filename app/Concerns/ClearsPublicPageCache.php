<?php

namespace App\Concerns;

use App\Support\PublicPageCache;

trait ClearsPublicPageCache
{
    public static function bootClearsPublicPageCache(): void
    {
        static::saved(fn () => PublicPageCache::forgetAll());
        static::deleted(fn () => PublicPageCache::forgetAll());
    }
}
