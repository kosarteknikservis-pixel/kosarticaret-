<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CatalogPaginationSeo
{
    /** @var list<string> */
    public const FILTER_QUERY_KEYS = ['q', 'marka', 'min', 'max', 'siralama'];

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
     * @return array{robots: string, paginationPrev: ?string, paginationNext: ?string}
     */
    public static function meta(Request $request, LengthAwarePaginator $paginator): array
    {
        return [
            'robots' => self::robots($request, $paginator->currentPage()),
            'paginationPrev' => $paginator->previousPageUrl(),
            'paginationNext' => $paginator->nextPageUrl(),
        ];
    }

    public static function robots(Request $request, int $currentPage = 1): string
    {
        if (self::hasActiveFilters($request) || $currentPage > 1) {
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
