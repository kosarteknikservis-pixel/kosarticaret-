<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CatalogPaginationSeo
{
    /** @var list<string> */
    public const FILTER_QUERY_KEYS = ['q', 'marka', 'min', 'max', 'siralama'];

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
