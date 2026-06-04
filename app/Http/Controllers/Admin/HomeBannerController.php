<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\HomeRow;
use App\Models\Product;
use App\Support\HomeBannerSpec;
use App\Support\HomeProductList;
use App\Support\ImageVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeBannerController extends Controller
{
    public function index(): View
    {
        return view('admin.home-banners.index', [
            'banners' => HomeBanner::query()->with(['product', 'category'])->orderBy('sort_order')->orderBy('id')->get(),
            'spec' => HomeBannerSpec::all(),
        ]);
    }

    public function builder(): View
    {
        $this->repairOrphanBlocks();

        $rows = HomeRow::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['banners' => fn ($q) => $q->with(['product', 'category', 'brand'])->orderBy('col_index')->orderBy('sort_order')])
            ->get();

        if ($rows->isEmpty()) {
            $row = HomeRow::query()->create([
                'name' => 'Satır 1',
                'columns' => [12],
                'sort_order' => 0,
            ]);
            $rows = collect([$row]);
        }

        return view('admin.home-banners.builder', [
            'rows' => $rows,
            'spec' => HomeBannerSpec::all(),
            'layoutPresets' => HomeRow::LAYOUTS,
        ]);
    }

    public function saveLayout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.id' => ['required', 'integer', 'exists:home_rows,id'],
            'rows.*.columns' => ['required', 'array'],
            'rows.*.columns.*' => ['array'],
            'rows.*.columns.*.*' => ['integer', 'exists:home_banners,id'],
        ]);

        foreach ($data['rows'] as $rowOrder => $rowPayload) {
            HomeRow::query()->whereKey($rowPayload['id'])->update(['sort_order' => $rowOrder]);

            foreach ($rowPayload['columns'] as $colIndex => $blockIds) {
                foreach (array_values($blockIds) as $sortOrder => $blockId) {
                    HomeBanner::query()->whereKey($blockId)->update([
                        'home_row_id' => $rowPayload['id'],
                        'col_index' => (int) $colIndex,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    public function storeRow(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preset' => ['required', Rule::in(array_keys(HomeRow::LAYOUTS))],
            'name' => ['nullable', 'string', 'max:80'],
        ]);

        $row = HomeRow::query()->create([
            'name' => $data['name'] ?? null,
            'columns' => HomeRow::layoutFromPreset($data['preset']),
            'sort_order' => (int) HomeRow::query()->max('sort_order') + 1,
        ]);

        return response()->json(['ok' => true, 'id' => $row->id]);
    }

    public function destroyRow(HomeRow $homeRow): JsonResponse
    {
        $fallback = HomeRow::query()->whereKeyNot($homeRow->id)->orderBy('sort_order')->value('id');
        if ($fallback) {
            HomeBanner::query()->where('home_row_id', $homeRow->id)->update([
                'home_row_id' => $fallback,
                'col_index' => 0,
            ]);
        } else {
            HomeBanner::query()->where('home_row_id', $homeRow->id)->update(['home_row_id' => null]);
        }
        $homeRow->delete();

        return response()->json(['ok' => true]);
    }

    public function panel(HomeBanner $homeBanner): View
    {
        $homeBanner->load(['product', 'category', 'brand']);

        return view('admin.home-banners.panel', $this->formData($homeBanner) + ['isNew' => false]);
    }

    public function panelCreate(Request $request): View
    {
        $type = $request->query('type', HomeBanner::TYPE_SLIDER);
        if (! in_array($type, HomeBanner::TYPES, true)) {
            $type = HomeBanner::TYPE_SLIDER;
        }

        $rowId = $request->integer('row_id') ?: HomeRow::query()->orderBy('sort_order')->value('id');
        $colIndex = $request->integer('col_index');

        $defaults = [
            'type' => $type,
            'home_row_id' => $rowId,
            'col_index' => $colIndex,
            'active' => true,
            'sort_order' => 0,
        ];
        if ($type === HomeBanner::TYPE_PRODUCT_LIST) {
            $defaults['product_source'] = 'latest';
            $defaults['product_limit'] = 4;
        }

        return view('admin.home-banners.panel', $this->formData(new HomeBanner($defaults)) + ['isNew' => true]);
    }

    public function quickPatch(Request $request, HomeBanner $homeBanner): JsonResponse
    {
        $data = $request->validate([
            'active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('active', $data)) {
            $homeBanner->update(['active' => (bool) $data['active']]);
        }

        return response()->json([
            'ok' => true,
            'active' => $homeBanner->active,
        ]);
    }

    public function updateDimensions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'home_banner_width' => ['required', 'integer', 'min:400', 'max:3840'],
            'home_banner_height' => ['required', 'integer', 'min:200', 'max:2160'],
        ]);

        HomeBannerSpec::save((int) $data['home_banner_width'], (int) $data['home_banner_height']);

        if ($request->boolean('from_builder')) {
            return redirect()->route('admin.home-banners.builder')->with('success', 'Slider / banner ölçüsü kaydedildi.');
        }

        return redirect()->route('admin.home-banners.index')->with('success', 'Slider / banner ölçüsü kaydedildi.');
    }

    public function create(): View
    {
        return view('admin.home-banners.form', $this->formData(new HomeBanner([
            'type' => HomeBanner::TYPE_SLIDER,
            'active' => true,
            'sort_order' => (int) HomeBanner::query()->max('sort_order') + 1,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $banner = HomeBanner::query()->create($this->validated($request, null));
        $this->repairOrphanBlocks();

        $message = 'Vitrin öğesi eklendi.';
        if ($banner->isProductList()) {
            $n = HomeProductList::availableCount($banner->fresh());
            $message .= " ({$n} ürün vitrinde)";
        }

        return $this->redirectAfterSave($request, $message);
    }

    public function edit(HomeBanner $homeBanner): View
    {
        $homeBanner->load(['product', 'category', 'brand']);

        return view('admin.home-banners.form', $this->formData($homeBanner));
    }

    public function update(Request $request, HomeBanner $homeBanner): RedirectResponse
    {
        $homeBanner->update($this->validated($request, $homeBanner));

        return $this->redirectAfterSave($request, 'Vitrin öğesi güncellendi.');
    }

    public function destroy(Request $request, HomeBanner $homeBanner): RedirectResponse
    {
        $this->deleteUploadedImage($homeBanner->image);
        $homeBanner->delete();

        if ($request->boolean('from_builder')) {
            return redirect()->route('admin.home-banners.builder')->with('success', 'Blok silindi.');
        }

        return redirect()->route('admin.home-banners.index')->with('success', 'Vitrin öğesi silindi.');
    }

    private function redirectAfterSave(Request $request, string $message): RedirectResponse
    {
        if ($request->boolean('from_builder')) {
            return redirect()->route('admin.home-banners.builder')->with('success', $message);
        }

        return redirect()->route('admin.home-banners.index')->with('success', $message);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:home_banners,id'],
        ]);

        foreach ($data['order'] as $position => $id) {
            HomeBanner::query()->whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function formData(HomeBanner $banner): array
    {
        return [
            'banner' => $banner,
            'spec' => HomeBannerSpec::all(),
            'rows' => HomeRow::query()->orderBy('sort_order')->get(),
            'products' => Product::query()->active()->orderBy('name')->limit(500)->get(['id', 'name', 'slug']),
            'categories' => Category::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'productSources' => HomeProductList::SOURCES,
            'listPreviewCount' => $banner->isProductList() ? HomeProductList::availableCount($banner) : null,
        ];
    }

    private function validated(Request $request, ?HomeBanner $banner): array
    {
        $type = $request->input('type', $banner?->type ?? HomeBanner::TYPE_SLIDER);

        $rules = [
            'type' => ['required', Rule::in(HomeBanner::TYPES)],
            'title' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:80'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'product_id' => ['nullable', 'exists:products,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_source' => ['nullable', Rule::in(HomeProductList::SOURCES)],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'product_limit' => ['nullable', 'integer', 'min:1', 'max:24'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.HomeBannerSpec::all()['max_kb']],
        ];

        if (in_array($type, [HomeBanner::TYPE_SLIDER, HomeBanner::TYPE_BANNER], true) && ! $banner?->exists && ! $request->hasFile('image_file')) {
            $rules['image_file'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.HomeBannerSpec::all()['max_kb']];
        }

        if ($type === HomeBanner::TYPE_PRODUCT) {
            $rules['product_id'] = ['required', 'exists:products,id'];
            $rules['category_id'] = ['nullable'];
        }

        if ($type === HomeBanner::TYPE_CATEGORY) {
            $rules['category_id'] = ['required', 'exists:categories,id'];
            $rules['product_id'] = ['nullable'];
        }

        if ($type === HomeBanner::TYPE_PRODUCT_LIST) {
            $rules['product_source'] = ['required', Rule::in(HomeProductList::SOURCES)];
            $rules['product_limit'] = ['required', 'integer', 'min:1', 'max:24'];
            $source = $request->input('product_source', 'latest');
            if ($source === 'category') {
                $rules['category_id'] = ['required', 'exists:categories,id'];
            }
            if ($source === 'brand') {
                $rules['brand_id'] = ['required', 'exists:brands,id'];
            }
            if ($source === 'manual') {
                $rules['product_ids'] = ['required', 'array', 'min:1'];
            }
        }

        $data = $request->validate($rules);

        $data['active'] = $request->boolean('active', true);
        $data['sort_order'] = $data['sort_order'] ?? ($banner?->sort_order ?? 0);
        $data['type'] = $type;
        $data['home_row_id'] = $request->input('home_row_id', $banner?->home_row_id);
        $data['col_index'] = (int) $request->input('col_index', $banner?->col_index ?? 0);

        if (empty($data['home_row_id'])) {
            $data['home_row_id'] = HomeRow::query()->orderBy('sort_order')->value('id');
        }

        if (in_array($type, [HomeBanner::TYPE_PRODUCT, HomeBanner::TYPE_CATEGORY, HomeBanner::TYPE_PRODUCT_LIST], true)) {
            $data['link_url'] = null;
        }

        if ($type === HomeBanner::TYPE_PRODUCT) {
            $data['category_id'] = null;
            $data['brand_id'] = null;
            $data['product_source'] = null;
            $data['product_ids'] = null;
        }

        if ($type === HomeBanner::TYPE_CATEGORY) {
            $data['product_id'] = null;
            $data['brand_id'] = null;
            $data['product_source'] = null;
            $data['product_ids'] = null;
        }

        if ($type === HomeBanner::TYPE_PRODUCT_LIST) {
            $data['product_id'] = null;
            $data['image'] = null;
            $source = $data['product_source'];
            if ($source !== 'category') {
                $data['category_id'] = null;
            }
            if ($source !== 'brand') {
                $data['brand_id'] = null;
            }
            if ($source !== 'manual') {
                $data['product_ids'] = null;
            } else {
                $data['product_ids'] = array_values(array_unique(array_map('intval', $data['product_ids'] ?? [])));
            }
        }

        if (in_array($type, [HomeBanner::TYPE_SLIDER, HomeBanner::TYPE_BANNER], true)) {
            $data['product_id'] = null;
            $data['category_id'] = null;
            $data['brand_id'] = null;
            $data['product_source'] = null;
            $data['product_ids'] = null;
        }

        if ($request->boolean('remove_image') && $banner?->image) {
            $this->deleteUploadedImage($banner->image);
            $data['image'] = null;
        } elseif ($request->hasFile('image_file')) {
            if ($banner?->image) {
                $this->deleteUploadedImage($banner->image);
            }
            $data['image'] = $request->file('image_file')->store('banners', 'public');
            ImageVariant::generate($data['image'], ImageVariant::presetsFor('banner'));
        } elseif ($banner) {
            unset($data['image']);
        }

        unset($data['image_file']);

        return $data;
    }

    private function deleteUploadedImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http') && str_starts_with($path, 'banners/')) {
            ImageVariant::delete($path);
            Storage::disk('public')->delete($path);
        }
    }

    /** Satırı silinmiş veya panel hatasıyla kopmuş blokları ilk satıra bağlar. */
    private function repairOrphanBlocks(): int
    {
        $rowId = HomeRow::query()->orderBy('sort_order')->value('id');
        if (! $rowId) {
            return 0;
        }

        $nextSort = ((int) HomeBanner::query()->where('home_row_id', $rowId)->max('sort_order')) + 1;

        return HomeBanner::query()
            ->whereNull('home_row_id')
            ->update([
                'home_row_id' => $rowId,
                'col_index' => 0,
                'sort_order' => $nextSort,
            ]);
    }
}
