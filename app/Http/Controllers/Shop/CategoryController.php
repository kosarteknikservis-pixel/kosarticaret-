<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\CatalogQuery;
use App\Support\CatalogPaginationSeo;
use App\Support\CategoryBreadcrumbs;
use App\Support\CategoryLandingPresenter;
use App\Support\InternalLinking;
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
            'metaTitle' => 'Tüm Ürün Kategorileri — Pompa, Hidrofor, Vantilatör | '.SiteName::get(),
            'metaDescription' => Seo::description([
                SiteName::get().' ürün kategorileri — dalgıç pompa, hidrofor sistemi, santrifüj pompa, vantilatör ve yedek parça grupları.',
            ]),
            'canonical' => route('categories.index'),
            'jsonLd' => [
                Seo::webPage('Tüm Ürün Kategorileri', Seo::description(['Pompa, hidrofor, vantilatör ve ekipman kategorileri']), route('categories.index')),
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

        if ($redirect = CatalogPaginationSeo::redirectLegacyPageParam($request)) {
            return $redirect;
        }

        $query = CatalogQuery::products()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with('brand');
        CatalogQuery::apply($request, $query);

        $breadcrumbs = CategoryBreadcrumbs::for($category);

        $category->load([
            'parent',
            'activeChildren' => fn ($q) => $q->orderBy('sort_order'),
        ]);
        $landing = CategoryLandingPresenter::for($category);

        $siblingCategories = collect();
        if ($category->activeChildren->isEmpty() && $category->parent_id) {
            $siblingCategories = $category->activeSiblings()->limit(12)->get();
        }

        $hubCategories = InternalLinking::crossSellCategories($category)
            ->reject(fn (Category $related) => $siblingCategories->contains('id', $related->id))
            ->values();

        $products = $query->paginate(12)->withQueryString();
        $pageUrl = $category->storefrontUrl();
        $paginationSeo = CatalogPaginationSeo::meta($request, $products);
        if ($category->activeChildren->isEmpty() && $products->total() === 0) {
            $paginationSeo['robots'] = Seo::ROBOTS_NOINDEX;
        }

        return view('shop.categories.show', [
            'category' => $category,
            'products' => $products,
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
            'breadcrumbs' => $breadcrumbs,
            'heroSubtitle' => $landing['subtitle'],
            'buyingGuide' => $landing['buying_guide'],
            'faq' => $landing['faq'],
            'trustPoints' => $landing['trust'],
            'subcategories' => $category->activeChildren,
            'siblingCategories' => $siblingCategories,
            'hubCategories' => $hubCategories,
            'metaTitle' => $category->meta_title ?: $category->name,
            'metaDescription' => Seo::description([
                $category->meta_description,
                $category->description,
                $category->name.' ürünleri — '.SiteName::get(),
            ]),
            'metaKeywords' => Seo::keywords([$category->name, SiteName::get()]),
            'canonical' => $pageUrl,
            'ogImageMeta' => Seo::openGraphImage($category->image, 'category-card', $category->name),
            'ogImage' => $category->imageUrl('category-card') ?? $category->imageUrl(),
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
