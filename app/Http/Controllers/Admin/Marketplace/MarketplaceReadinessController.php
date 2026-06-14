<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Marketplace\ProductReadinessChecker;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceReadinessController extends Controller
{
    public function __invoke(Request $request, ProductReadinessChecker $readiness): View
    {
        $filter = $request->query('filter', 'all');

        $query = Product::query()
            ->with(['brand:id,name', 'categories:id,name'])
            ->where('is_active', true)
            ->where('marketplace_enabled', true);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($builder) use ($q): void {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhere('barcode', 'like', '%'.$q.'%');
            });
        }

        match ($filter) {
            'missing_barcode' => $query->where(function ($q): void {
                $q->whereNull('barcode')->orWhere('barcode', '');
            }),
            'missing_brand' => $query->whereNull('brand_id'),
            'missing_category' => $query->whereDoesntHave('categories'),
            'missing_image' => $query->where(function ($q): void {
                $q->whereNull('image')->orWhere('image', '');
            }),
            default => null,
        };

        $products = $query->latest()->paginate(30)->withQueryString();

        $rows = $products->getCollection()->map(function (Product $product) use ($readiness) {
            $evaluation = $readiness->evaluate($product);

            return [
                'product' => $product,
                'score' => $evaluation['score'],
                'ready' => $evaluation['ready'],
                'missing' => collect($evaluation['checks'])
                    ->reject(fn (array $check) => $check['passed'])
                    ->pluck('label')
                    ->all(),
            ];
        });

        return view('admin.marketplace.readiness', [
            'products' => $products,
            'rows' => $rows,
            'filter' => $filter,
        ]);
    }
}
