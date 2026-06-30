<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\CatalogQuery;
use App\Support\CatalogPaginationSeo;
use App\Support\CategoryBreadcrumbs;
use App\Support\CategoryLandingPresenter;
use App\Support\Seo;
use App\Support\SiteName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('shop.categories.index', [
            'categories' => Category::query()->whereNull('parent_id')->where('active', true)->orderBy('sort_order')->get(),
            'metaTitle' => 'Kategoriler',
            'metaDescription' => Seo::description([
                SiteName::get().' ürün kategorileri — pompa, hidrofor, fan ve ekipman grupları.',
            ]),
            'canonical' => route('categories.index'),
            'jsonLd' => [
                Seo::webPage('Kategoriler', Seo::description(['Ürün kategorileri']), route('categories.index')),
            ],
        ]);
    }

    public function show(Request $request, Category $category): View|RedirectResponse
    {
        $requestedPath = $this->requestedCategoryPath($request);
        $canonicalPath = $category->nestedSlugPath();
        if ($requestedPath !== '' && $requestedPath !== $canonicalPath) {
            return redirect()->to($category->storefrontUrl(), 301);
        }

        $query = CatalogQuery::products()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with('brand');
        CatalogQuery::apply($request, $query);

        $breadcrumbs = CategoryBreadcrumbs::for($category);

        $category->load(['activeChildren' => fn ($q) => $q->orderBy('sort_order')]);
        $landing = CategoryLandingPresenter::for($category);

        $products = $query->paginate(12)->withQueryString();
        $pageUrl = $category->storefrontUrl();
        $paginationSeo = CatalogPaginationSeo::meta($request, $products);

        return view('shop.categories.show', [
            'category' => $category,
            'products' => $products,
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
            'breadcrumbs' => $breadcrumbs,
            'heroSubtitle' => $landing['subtitle'],
            'buyingGuide' => $landing['buying_guide'],
            'trustPoints' => $landing['trust'],
            'subcategories' => $category->activeChildren,
            'metaTitle' => $category->meta_title ?: $category->name,
            'metaDescription' => Seo::description([
                $category->meta_description,
                $category->description,
                $category->name.' ürünleri — '.SiteName::get(),
            ]),
            'metaKeywords' => Seo::keywords([$category->name, SiteName::get()]),
            'canonical' => $pageUrl,
            'ogImage' => $category->imageUrl(),
            'jsonLd' => array_filter([
                Seo::category($category),
                Seo::breadcrumbs($breadcrumbs),
                Seo::itemListProducts($products, $pageUrl, $products->total()),
            ]),
            ...$paginationSeo,
        ]);
    }

    private function requestedCategoryPath(Request $request): string
    {
        $path = $request->path();
        if (! str_starts_with($path, 'kategoriler/')) {
            return '';
        }

        return urldecode(Str::after($path, 'kategoriler/'));
    }
}
