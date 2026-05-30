<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Catalog\BulkProductUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class BulkProductUpdateController extends Controller
{
    public function __construct(private BulkProductUpdateService $bulk) {}

    public function index(): View
    {
        return view('admin.products.bulk-update', [
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->with('parent')->orderBy('name')->get(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $filters = $this->parseFilters($request);

        return response()->json([
            'count' => $this->bulk->countMatching($filters),
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $filters = $this->parseFilters($request);
        $actions = $this->parseActions($request);

        if (! $request->boolean('confirm')) {
            return back()->withErrors(['confirm' => 'Güncellemeyi onaylamanız gerekiyor.'])->withInput();
        }

        try {
            $stats = $this->bulk->apply($filters, $actions);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['bulk' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.products.bulk-update')
            ->with('success', sprintf(
                '%d ürün eşleşti, %d ürün güncellendi.',
                $stats['matched'],
                $stats['updated']
            ));
    }

    public function applyCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'confirm' => ['accepted'],
        ]);

        $path = $request->file('csv_file')->getRealPath();
        if ($path === false) {
            return back()->withErrors(['csv_file' => 'Dosya okunamadı.']);
        }

        try {
            $stats = $this->bulk->applyCsv($path);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['csv_file' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.products.bulk-update', ['tab' => 'csv'])
            ->with('success', sprintf(
                'CSV: %d satır, %d eşleşen SKU, %d ürün güncellendi (%d atlandı).',
                $stats['csv_rows'],
                $stats['matched'],
                $stats['updated'],
                $stats['skipped']
            ));
    }

    /** @return array<string, mixed> */
    private function parseFilters(Request $request): array
    {
        return [
            'category_ids' => $request->input('filter_category_ids', []),
            'brand_id' => $request->input('filter_brand_id'),
            'stock' => $request->input('filter_stock', 'any'),
            'stock_low_max' => $request->input('filter_stock_low_max', 5),
            'featured' => $request->input('filter_featured', 'any'),
            'is_active' => $request->input('filter_is_active', 'any'),
            'search' => $request->input('filter_search'),
            'sku_list' => $request->input('filter_sku_list'),
            'product_ids' => $request->input('filter_product_ids', []),
        ];
    }

    /** @return array<string, mixed> */
    private function parseActions(Request $request): array
    {
        $actions = $this->bulk->emptyActions();

        if ($request->boolean('act_price')) {
            $actions['price'] = [
                'mode' => $request->input('price_mode', 'none'),
                'value' => (float) str_replace(',', '.', (string) $request->input('price_value', 0)),
            ];
        }

        if ($request->boolean('act_compare')) {
            $actions['compare'] = [
                'mode' => $request->input('compare_mode', 'none'),
                'value' => (float) str_replace(',', '.', (string) $request->input('compare_value', 0)),
            ];
        }

        if ($request->boolean('act_stock')) {
            $actions['stock'] = [
                'mode' => $request->input('stock_mode', 'none'),
                'value' => (int) $request->input('stock_value', 0),
            ];
        }

        if ($request->boolean('act_brand')) {
            $actions['brand'] = [
                'mode' => $request->input('brand_mode', 'none'),
                'brand_id' => $request->input('brand_id') ?: null,
            ];
        }

        if ($request->boolean('act_categories')) {
            $actions['categories'] = [
                'mode' => $request->input('category_mode', 'none'),
                'ids' => $request->input('category_ids', []),
            ];
        }

        if ($request->boolean('act_status')) {
            $actions['status'] = [
                'is_active' => $request->input('status_is_active', 'no_change'),
                'featured' => $request->input('status_featured', 'no_change'),
            ];
        }

        if ($request->boolean('act_tags')) {
            $actions['tags'] = [
                'mode' => $request->input('tags_mode', 'none'),
                'value' => (string) $request->input('tags_value', ''),
            ];
        }

        foreach (['meta_title', 'meta_description', 'image_alt', 'short_description'] as $field) {
            if ($request->boolean('act_'.$field)) {
                $actions['text'][$field] = [
                    'mode' => $request->input($field.'_mode', 'none'),
                    'value' => (string) $request->input($field.'_value', ''),
                ];
            }
        }

        return $actions;
    }
}
