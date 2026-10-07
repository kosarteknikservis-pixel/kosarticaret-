<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Collection;
use App\Support\CatalogPaginationSeo;
use App\Support\Seo;
use Illuminate\Http\Request;

class CollectionPageData
{
    public function __construct(private CollectionIntro $intro) {}

    /** @return array<string, mixed> */
    public function data(Request $request, Collection $collection, bool $preview = false): array
    {
        $pageUrl = route('collections.show', $collection);
        $query = $collection->visibleProducts()->with('brand', 'categories');
        CatalogQuery::apply($request, $query);
        $products = $query->paginate(12)->withQueryString();
        $pagination = CatalogPaginationSeo::meta($request, $products, $pageUrl);
        if ($preview || $collection->status !== Collection::STATUS_INDEX) {
            $pagination['robots'] = $preview ? 'noindex, nofollow' : Seo::ROBOTS_NOINDEX;
        }

        $sentence = $this->intro->sentence($collection);
        $brandIds = $collection->visibleProducts()->pluck('brand_id')->filter()->unique()->values();
        $breadcrumbs = [
            ['name' => 'Ana Sayfa', 'url' => route('home')],
            ['name' => $collection->name, 'url' => $pageUrl],
        ];

        return [
            'collection' => $collection,
            'products' => $products,
            'sentence' => $sentence,
            'preview' => $preview,
            'relatedCategories' => $collection->category ? collect([$collection->category]) : collect(),
            'brands' => Brand::query()->whereIn('id', $brandIds)->where('active', true)->orderBy('name')->get(),
            'breadcrumbs' => $breadcrumbs,
            'faq' => $collection->faq ?? [],
            'metaTitle' => $collection->name,
            'metaDescription' => Seo::description([$sentence]),
            'canonical' => $preview ? url()->current() : $pagination['canonical'],
            'jsonLd' => array_filter([
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $collection->name,
                    'url' => $pageUrl,
                    'description' => $sentence,
                    'inLanguage' => 'tr-TR',
                ]),
                Seo::breadcrumbs($breadcrumbs),
                Seo::itemListProducts($products, $pageUrl, $products->total()),
            ]),
            ...$pagination,
            'canonical' => $preview ? url()->current() : $pagination['canonical'],
            'robots' => $pagination['robots'],
        ];
    }
}
