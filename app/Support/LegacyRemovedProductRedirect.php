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

        $categoryTarget = LegacySlugCategoryGuesser::pathForSlug($slug);
        if ($categoryTarget !== null) {
            return $categoryTarget;
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
