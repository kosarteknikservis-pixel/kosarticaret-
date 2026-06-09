<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCompareController extends Controller
{
    private const SESSION_KEY = 'compare_slugs';

    private const MAX_ITEMS = 3;

    public function index(Request $request): View
    {
        $products = $this->resolveProducts($request);

        return view('shop.products.compare', [
            'products' => $products,
            'metaTitle' => __('shop.compare_title'),
            'metaDescription' => __('shop.compare_meta'),
            'canonical' => route('compare.index'),
            'robots' => 'noindex, follow',
        ]);
    }

    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $slugs = $this->slugs($request);

        if (! in_array($product->slug, $slugs, true)) {
            if (count($slugs) >= self::MAX_ITEMS) {
                array_shift($slugs);
            }
            $slugs[] = $product->slug;
        }

        $request->session()->put(self::SESSION_KEY, $slugs);

        if ($request->expectsJson()) {
            return response()->json([
                'count' => count($slugs),
                'slugs' => $slugs,
                'message' => __('shop.compare_added'),
            ]);
        }

        return back()->with('success', __('shop.compare_added'));
    }

    public function remove(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $slugs = array_values(array_filter(
            $this->slugs($request),
            fn (string $s) => $s !== $slug
        ));

        $request->session()->put(self::SESSION_KEY, $slugs);

        if ($request->expectsJson()) {
            return response()->json(['count' => count($slugs), 'slugs' => $slugs]);
        }

        return back();
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'count' => count($this->slugs($request)),
            'slugs' => $this->slugs($request),
            'max' => self::MAX_ITEMS,
        ]);
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        if ($request->expectsJson()) {
            return response()->json(['count' => 0, 'slugs' => []]);
        }

        return redirect()->route('compare.index');
    }

    /**
     * @return list<string>
     */
    private function slugs(Request $request): array
    {
        $slugs = $request->session()->get(self::SESSION_KEY, []);

        return is_array($slugs) ? array_values(array_filter($slugs, 'is_string')) : [];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function resolveProducts(Request $request)
    {
        $slugs = $this->slugs($request);
        if ($slugs === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->with(['brand', 'categories'])
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        return collect($slugs)
            ->map(fn (string $slug) => $products->get($slug))
            ->filter();
    }
}
