<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->string('q')->trim()->toString();

        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'results' => []]);
        }

        $products = \App\Services\CatalogQuery::products()
            ->where('stock', '>', 0)
            ->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            })
            ->with('brand')
            ->orderByDesc('featured')
            ->limit(6)
            ->get()
            ->map(fn (Product $p) => [
                'type' => 'product',
                'name' => $p->name,
                'meta' => $p->brand?->name,
                'url' => route('products.show', $p),
                'price' => number_format($p->price, 2, ',', '.').' ₺',
                'image' => $p->imageUrl('product-thumb'),
            ]);

        $categories = Category::query()
            ->where('active', true)
            ->where('name', 'like', "%{$q}%")
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(fn (Category $c) => [
                'type' => 'category',
                'name' => $c->name,
                'meta' => __('shop.categories'),
                'url' => $c->storefrontUrl(),
            ]);

        $brands = Brand::query()
            ->where('active', true)
            ->where('name', 'like', "%{$q}%")
            ->limit(2)
            ->get()
            ->map(fn (Brand $b) => [
                'type' => 'brand',
                'name' => $b->name,
                'meta' => __('shop.brands'),
                'url' => route('brands.show', $b),
            ]);

        $results = $categories->concat($brands)->concat($products)->take(10)->values();

        return response()->json([
            'ok' => true,
            'query' => $q,
            'results' => $results,
            'search_url' => route('search', ['q' => $q]),
        ]);
    }
}
