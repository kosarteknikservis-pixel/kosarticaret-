<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CatalogPaginationSeo
{
    /**
     * @return array{robots: string, paginationPrev: ?string, paginationNext: ?string}
     */
    public static function meta(Request $request, LengthAwarePaginator $paginator): array
    {
        $robots = $paginator->currentPage() > 1 ? 'noindex, follow' : 'index, follow';

        return [
            'robots' => $robots,
            'paginationPrev' => $paginator->previousPageUrl(),
            'paginationNext' => $paginator->nextPageUrl(),
        ];
    }
}
