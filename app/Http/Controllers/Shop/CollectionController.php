<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Services\CollectionPageData;
use App\Support\CatalogPaginationSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function show(Request $request, Collection $collection, CollectionPageData $pages): View|RedirectResponse
    {
        if ($redirect = CatalogPaginationSeo::redirectLegacyPageParam($request)) {
            return $redirect;
        }

        abort_unless($collection->isPublic(), 404);

        return view('shop.collections.show', $pages->data($request, $collection));
    }
}
