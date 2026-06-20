<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\CatalogQuery;
use App\Services\SearchAnalyticsService;
use App\Support\CatalogPaginationSeo;
use App\Support\Seo;
use App\Support\SiteName;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = $request->string('q')->trim();
        $query = CatalogQuery::products()->with('brand', 'categories');
        CatalogQuery::apply($request, $query);

        $products = $query->paginate(12)->withQueryString();
        $paginationSeo = CatalogPaginationSeo::meta($request, $products);

        if ($q !== '') {
            app(SearchAnalyticsService::class)->record($request, (string) $q, $products->total());
        }

        $jsonLd = [
            Seo::webSite(),
            Seo::breadcrumbs([
                ['name' => 'Ana Sayfa', 'url' => route('home')],
                ['name' => 'Arama'],
            ]),
        ];

        return view('shop.search.index', [
            'search' => $q,
            'products' => $products,
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
            'jsonLd' => $jsonLd,
            'metaTitle' => $q ? "“{$q}” arama sonuçları" : 'Ürün Ara',
            'metaDescription' => Seo::description([
                $q ? "{$q} için ".SiteName::get().' ürün arama sonuçları.' : null,
                config('kosar.description'),
            ]),
            'canonical' => route('search', $q ? ['q' => $q] : []),
            ...$paginationSeo,
        ]);
    }
}
