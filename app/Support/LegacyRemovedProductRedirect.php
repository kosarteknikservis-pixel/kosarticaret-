<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

final class LegacyRemovedProductRedirect
{
    public static function targetForSlug(string $slug): ?string
    {
        if (self::isActiveSlug($slug)) {
            return null;
        }

        $exact = config('legacy_redirects.exact', []);
        $key = '/urun/'.$slug;
        if (isset($exact[$key])) {
            return (string) $exact[$key];
        }

        $generated = config('legacy_product_redirects', []);
        if (isset($generated[$key])) {
            return (string) $generated[$key];
        }

        foreach (config('legacy_redirects.product_brand_prefixes', []) as $prefix => $brandSlug) {
            if (! self::matchesBrandPrefix($slug, (string) $prefix)) {
                continue;
            }

            return $brandSlug !== null && $brandSlug !== ''
                ? '/marka/'.$brandSlug
                : (string) config('legacy_redirects.removed_product_fallback', '/urunler');
        }

        return (string) config('legacy_redirects.removed_product_fallback', '/urunler');
    }

    private static function isActiveSlug(string $slug): bool
    {
        $slugs = Cache::remember('legacy_redirect.active_product_slugs', 3600, function () {
            return Product::query()
                ->active()
                ->pluck('slug')
                ->flip()
                ->all();
        });

        return isset($slugs[$slug]);
    }

    private static function matchesBrandPrefix(string $slug, string $prefix): bool
    {
        return $slug === $prefix
            || str_starts_with($slug, $prefix.'-');
    }
}
