<?php

namespace App\Support;

use Illuminate\Http\Request;

final class LegacyRedirectResolver
{
    public static function resolve(Request $request): ?string
    {
        $path = self::normalizePath($request->path());
        if ($path === '/') {
            return self::resolveHomeLegacyQuery($request);
        }

        $exact = config('legacy_redirects.exact', []);
        if (isset($exact[$path])) {
            return self::normalizeTarget((string) $exact[$path]);
        }

        foreach (config('legacy_redirects.patterns', []) as $rule) {
            $pattern = (string) ($rule['match'] ?? '');
            $target = (string) ($rule['to'] ?? '');
            if ($pattern === '' || $target === '') {
                continue;
            }

            if (preg_match($pattern, $path, $matches)) {
                for ($i = 1; $i < count($matches); $i++) {
                    $target = str_replace('$'.$i, $matches[$i], $target);
                }

                return self::normalizeTarget($target);
            }
        }

        $blogPosts = config('legacy_redirects.blog_posts', []);
        if (isset($blogPosts[$path])) {
            return self::normalizeTarget((string) $blogPosts[$path]);
        }

        $legacyCategory = self::resolveLegacyCategoryPath($path);
        if ($legacyCategory !== null) {
            return $legacyCategory;
        }

        $legacyBrand = self::resolveLegacyBrandPath($path, $request);
        if ($legacyBrand !== null) {
            return $legacyBrand;
        }

        if (preg_match('#^/magaza(?:/page/\d+)?$#', $path)) {
            return self::normalizeTarget('/urunler');
        }

        if (preg_match('#^/page/\d+$#', $path)) {
            return self::normalizeTarget('/urunler');
        }

        if (preg_match('#^/sepet/page/\d+$#', $path)) {
            return self::normalizeTarget('/urunler');
        }

        if (preg_match('#^/urun-etiket(?:/|$)#', $path)) {
            return self::normalizeTarget('/urunler');
        }

        if (preg_match('#^/tag(?:/|$)#', $path)) {
            return self::normalizeTarget('/blog');
        }

        if (preg_match('#^/kategori/([^/]+)$#', $path, $matches)) {
            $aliases = config('legacy_redirects.category_aliases', []);
            $target = $aliases[$matches[1]] ?? null;

            return self::normalizeTarget($target !== null ? '/kategoriler/'.$target : '/kategoriler');
        }

        if (preg_match('#^[a-f0-9]{40}$#', ltrim($path, '/'))) {
            return self::normalizeTarget('/');
        }

        if ($path === '/sepet' && self::hasLegacyCartQuery($request)) {
            return self::normalizeTarget('/urunler');
        }

        if (preg_match('#^/urun/([^/]+)$#', $path, $matches) && self::hasLegacyProductQuery($request)) {
            return self::normalizeTarget('/urun/'.$matches[1]);
        }

        if (preg_match('#^/urun/([^/]+)/feed$#', $path, $matches)) {
            return self::normalizeTarget('/urun/'.$matches[1]);
        }

        return null;
    }

    private static function resolveHomeLegacyQuery(Request $request): ?string
    {
        if ($request->has('elementor_library')) {
            return self::normalizeTarget('/');
        }

        return null;
    }

    private static function resolveLegacyCategoryPath(string $path): ?string
    {
        if (! preg_match('#^/urun-kategori/(.+)$#', $path, $matches)) {
            return null;
        }

        $relative = preg_replace('#/page/\d+$#', '', $matches[1]);
        $map = config('legacy_redirects.category_paths', []);

        if (isset($map[$relative])) {
            $target = (string) $map[$relative];

            return self::normalizeTarget(str_starts_with($target, '/')
                ? $target
                : '/kategoriler/'.$target);
        }

        return self::normalizeTarget('/kategoriler');
    }

    private static function resolveLegacyBrandPath(string $path, Request $request): ?string
    {
        if (preg_match('#^/markalar/([^/]+)(?:/page/\d+)?$#', $path, $matches)) {
            return self::normalizeTarget('/marka/'.self::resolveBrandSlug($matches[1]));
        }

        if (preg_match('#^/marka/([^/]+)/page/\d+$#', $path, $matches)) {
            return self::normalizeTarget('/marka/'.self::resolveBrandSlug($matches[1]));
        }

        if (preg_match('#^/marka/([^/]+)$#', $path, $matches) && $request->has('filtering')) {
            return self::normalizeTarget('/marka/'.self::resolveBrandSlug($matches[1]));
        }

        $aliases = config('legacy_redirects.brand_aliases', []);
        if (preg_match('#^/marka/([^/]+)$#', $path, $matches) && isset($aliases[$matches[1]])) {
            return self::normalizeTarget('/marka/'.$aliases[$matches[1]]);
        }

        return null;
    }

    private static function resolveBrandSlug(string $slug): string
    {
        $aliases = config('legacy_redirects.brand_aliases', []);

        return (string) ($aliases[$slug] ?? $slug);
    }

    private static function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function hasLegacyCartQuery(Request $request): bool
    {
        return $request->hasAny([
            'filtering',
            'filter_product_brand',
            'filter_cat',
            'remove_item',
            'shop_view',
            'on_sale',
            'stock_status',
            'add-to-cart',
            '_wpnonce',
            'gridcookie',
        ]);
    }

    private static function hasLegacyProductQuery(Request $request): bool
    {
        return $request->hasAny([
            'add-to-cart',
            'add-to-compare',
            'added-to-cart',
        ]);
    }

    private static function normalizeTarget(string $target): string
    {
        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
            return $target;
        }

        return '/'.ltrim($target, '/');
    }
}
