<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CatalogPaginationSeo
{
    /** @var list<string> */
    public const FILTER_QUERY_KEYS = ['q', 'marka', 'min', 'max', 'siralama', 'voltaj'];

    /** @var list<string> */
    public const SORT_VALUES = ['fiyat-artan', 'fiyat-azalan', 'isim'];

    /**
     * Eski `?sayfa=` parametresini Laravel `page` standardına 301 ile taşı.
     */
    public static function redirectLegacyPageParam(Request $request): ?RedirectResponse
    {
        if (! $request->query->has('sayfa')) {
            return null;
        }

        $query = $request->query();
        $sayfa = max(1, (int) ($query['sayfa'] ?? 1));
        unset($query['sayfa']);

        if (! isset($query['page']) && $sayfa > 1) {
            $query['page'] = $sayfa;
        }

        $target = $request->url();
        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        if ($target === $request->fullUrl()) {
            return null;
        }

        return redirect()->to($target, 301);
    }

    /**
     * Eski/yanlış `?sirala=` parametresini kanonik `?siralama=` ile değiştir (301).
     * UI ve CatalogQuery yalnızca `siralama` okur; `sirala` sıralamayı değiştirmezdi.
     *
     * - İkisi birlikte: `siralama` kazanır, `sirala` düşer (döngü yok).
     * - Geçersiz `sirala`: düşülür, uydurma sıralama eklenmez.
     * - Geçerli `sirala` + diğer query (marka/page): korunur.
     */
    public static function redirectLegacySortParam(Request $request): ?RedirectResponse
    {
        if (! $request->query->has('sirala')) {
            return null;
        }

        $query = $request->query();
        $legacy = trim((string) ($query['sirala'] ?? ''));
        unset($query['sirala']);

        if ($legacy !== '' && ! isset($query['siralama']) && in_array($legacy, self::SORT_VALUES, true)) {
            $query['siralama'] = $legacy;
        }

        if (isset($query['siralama']) && ! in_array((string) $query['siralama'], self::SORT_VALUES, true)) {
            unset($query['siralama']);
        }

        $target = $request->url();
        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        if ($target === $request->fullUrl()) {
            return null;
        }

        return redirect()->to($target, 301);
    }

    /**
     * @return array{robots: string, canonical: string, paginationPrev: ?string, paginationNext: ?string}
     */
    public static function meta(Request $request, LengthAwarePaginator $paginator, string $baseUrl): array
    {
        $prev = $paginator->previousPageUrl();

        return [
            'robots' => self::robots($request, $paginator->currentPage()),
            'canonical' => self::canonical($baseUrl, $paginator->currentPage()),
            'paginationPrev' => $prev !== null ? self::stripPageOne($prev) : null,
            'paginationNext' => $paginator->nextPageUrl(),
        ];
    }

    private static function stripPageOne(string $url): string
    {
        $cleaned = preg_replace(['/([?&])page=1(&|$)/', '/[?&]$/'], ['$1', ''], $url) ?? $url;

        return $cleaned === '' ? $url : $cleaned;
    }

    /**
     * Filtre/sıralama parametreleri düşer; 2+ sayfa 1. sayfaya değil kendi adresine işaret eder.
     */
    public static function canonical(string $baseUrl, int $currentPage = 1): string
    {
        if ($currentPage <= 1) {
            return $baseUrl;
        }

        return $baseUrl.(str_contains($baseUrl, '?') ? '&' : '?').'page='.$currentPage;
    }

    public static function robots(Request $request, int $currentPage = 1): string
    {
        if (self::hasActiveFilters($request)) {
            return Seo::ROBOTS_NOINDEX;
        }

        return Seo::ROBOTS_INDEX;
    }

    public static function hasActiveFilters(Request $request): bool
    {
        foreach (self::FILTER_QUERY_KEYS as $key) {
            if ($request->filled($key)) {
                return true;
            }
        }

        return $request->boolean('stokta');
    }
}
