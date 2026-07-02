<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicPageCache
{
    public const TAG = 'public_pages';

    public const TTL_SECONDS = 900;

    /**
     * @var list<string>
     */
    private const EXCLUDED_PREFIXES = [
        'sepet',
        'odeme',
        'hesabim',
        'giris',
        'kayit',
        'yonetim',
        'karsilastir',
        'pompa-secici',
        'analitik',
        'favoriler',
        'siparis',
        'urun',
    ];

    /**
     * @var list<string>
     */
    private const EXCLUDED_EXACT = [
        'ara',
        'iletisim',
    ];

    public static function shouldCache(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($request->user()) {
            return false;
        }

        $cart = session('cart', []);
        if (is_array($cart) && array_sum($cart) > 0) {
            return false;
        }

        $favorites = session('favorites', []);
        if (is_array($favorites) && count($favorites) > 0) {
            return false;
        }

        $compare = session('compare_slugs', []);
        if (is_array($compare) && count($compare) > 0) {
            return false;
        }

        if (! config('kosar.public_page_cache', true)) {
            return false;
        }

        $path = trim($request->path(), '/');

        if (in_array($path, self::EXCLUDED_EXACT, true)) {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return false;
            }
        }

        return true;
    }

    public static function key(Request $request): string
    {
        $locale = app()->getLocale();

        return 'public_page:'.hash('sha256', $locale.'|'.$request->getRequestUri());
    }

    public static function forgetAll(): void
    {
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags([self::TAG])->flush();

            return;
        }

        // File/database driver: bump version counter used in keys (fallback flush not global)
        Cache::put(self::TAG.':version', (int) Cache::get(self::TAG.':version', 0) + 1, 86400);
    }

    public static function versionSuffix(): string
    {
        return (string) Cache::get(self::TAG.':version', '0');
    }
}
