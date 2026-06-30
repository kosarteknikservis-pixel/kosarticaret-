<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\CatalogQuery;
use App\Support\CatalogPaginationSeo;
use App\Support\Seo;
use App\Support\SiteName;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('shop.brands.index', [
            'brands' => Brand::query()->where('active', true)->orderBy('sort_order')->get(),
            'metaTitle' => 'Markalar',
            'metaDescription' => Seo::description([
                SiteName::get().' distribütör ve üretici markaları — orijinal ürün garantisi.',
            ]),
            'canonical' => route('brands.index'),
            'jsonLd' => [
                Seo::webPage('Markalar', Seo::description(['Marka listesi']), route('brands.index')),
            ],
        ]);
    }

    public function show(Request $request, Brand $brand): View
    {
        $query = CatalogQuery::products()->where('brand_id', $brand->id)->with('brand', 'categories');
        CatalogQuery::apply($request, $query);

        $breadcrumbs = [
            ['name' => 'Ana Sayfa', 'url' => route('home')],
            ['name' => 'Markalar', 'url' => route('brands.index')],
            ['name' => $brand->name],
        ];

        $products = $query->paginate(12)->withQueryString();
        $pageUrl = route('brands.show', $brand);
        $paginationSeo = CatalogPaginationSeo::meta($request, $products);

        return view('shop.brands.show', [
            'brand' => $brand,
            'products' => $products,
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
            'breadcrumbs' => $breadcrumbs,
            'metaTitle' => $brand->meta_title ?: $brand->name.' '.config('seo.brand_page_title_suffix', 'Ürünleri ve Fiyatları'),
            'metaDescription' => Seo::description([
                $brand->meta_description,
                $brand->description,
                $brand->name.' marka ürünleri — '.SiteName::get(),
            ]),
            'metaKeywords' => Seo::keywords([$brand->name, SiteName::get()]),
            'canonical' => $pageUrl,
            'ogImage' => $brand->logoUrl(),
            'jsonLd' => array_filter([
                Seo::brand($brand),
                Seo::breadcrumbs($breadcrumbs),
                Seo::itemListProducts($products, $pageUrl, $products->total()),
            ]),
            ...$paginationSeo,
        ]);
    }
}
