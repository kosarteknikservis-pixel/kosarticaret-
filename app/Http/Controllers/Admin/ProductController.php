<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\BulkProductUpdateService;
use App\Services\Catalog\ProductCsvExporter;
use App\Support\ImageVariant;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        private BulkProductUpdateService $bulk,
        private ProductCsvExporter $exporter,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->catalogFilters($request);
        $query = $this->applySorting($this->bulk->filterQuery($filters), $request);

        return view('admin.products.index', [
            'products' => $query->with(['brand:id,name', 'categories:id,name'])
                ->paginate($this->perPage($request))
                ->withQueryString(),
            'filters' => $this->displayFilters($request),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->catalogFilters($request);

        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = array_filter(explode(',', (string) $ids));
        }
        if ($ids !== []) {
            $filters['product_ids'] = array_values(array_filter(array_map('intval', $ids)));
        }

        $query = $this->applySorting($this->bulk->filterQuery($filters), $request);

        return $this->exporter->download(
            $query,
            'urunler-'.now()->format('Y-m-d-His').'.csv'
        );
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->mergeImage($request, $data);
        $product = Product::query()->create($data);
        $product->categories()->sync($request->input('category_ids', []));
        $this->storeGalleryImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Ürün oluşturuldu.');
    }

    public function edit(Product $product): View
    {
        $product->load(['categories', 'images']);

        return view('admin.products.form', [
            'product' => $product,
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $data = $this->mergeImage($request, $data, $product);
        $product->update($data);
        $product->categories()->sync($request->input('category_ids', []));
        $this->storeGalleryImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Ürün silindi.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'brand_id' => $request->input('brand_id') ?: null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'gallery_files' => ['nullable', 'array', 'max:20'],
            'gallery_files.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $data['slug'] = SlugHelper::assign('products', $data['slug'] ?? null, $data['name'], $product?->id);
        $data['featured'] = $request->boolean('featured');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['tags'] = $request->filled('tags')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $request->input('tags')))))
            : [];

        if ($request->filled('translations')) {
            $translations = $request->input('translations');
            if (isset($translations['en']['description'])) {
                $translations['en']['description'] = RichContent::normalize($translations['en']['description']);
            }
            $data['translations'] = $translations;
        }

        $data['description'] = RichContent::normalize($data['description'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeImage(Request $request, array $data, ?Product $product = null): array
    {
        if ($request->hasFile('image_file')) {
            if ($product?->image && ! str_starts_with($product->image, 'http')) {
                ImageVariant::delete($product->image);
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image_file')->store('products', 'public');
            ImageVariant::generate($data['image'], ImageVariant::presetsFor('product'));
        } elseif (! $request->filled('image') && $product) {
            unset($data['image']);
        }

        return $data;
    }

    private function storeGalleryImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('gallery_files')) {
            return;
        }

        $sort = (int) $product->images()->max('sort_order');
        foreach ($request->file('gallery_files') as $file) {
            $sort++;
            $path = $file->store('products/gallery', 'public');
            ImageVariant::generate($path, ImageVariant::presetsFor('product-gallery'));

            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $sort,
            ]);
        }
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        ImageVariant::delete($image->path);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Galeri görseli silindi.');
    }

    /** @return array<string, mixed> */
    private function catalogFilters(Request $request): array
    {
        $categoryId = (int) $request->input('category_id', 0);

        return [
            'category_ids' => $categoryId > 0 ? [$categoryId] : [],
            'brand_id' => $request->input('brand_id'),
            'stock' => $request->filled('stock') ? $request->input('stock') : 'any',
            'stock_low_max' => 5,
            'featured' => $request->filled('featured') ? $request->input('featured') : 'any',
            'is_active' => $request->filled('is_active') ? $request->input('is_active') : 'any',
            'search' => $request->input('q'),
            'sku_list' => $request->input('sku_list'),
            'product_ids' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function displayFilters(Request $request): array
    {
        return [
            'q' => (string) $request->input('q', ''),
            'brand_id' => $request->input('brand_id', ''),
            'category_id' => $request->input('category_id', ''),
            'stock' => (string) $request->input('stock', ''),
            'is_active' => (string) $request->input('is_active', ''),
            'featured' => (string) $request->input('featured', ''),
            'sort' => (string) $request->input('sort', 'latest'),
            'per_page' => (string) $request->input('per_page', '20'),
            'sku_list' => (string) $request->input('sku_list', ''),
        ];
    }

    /** @param  Builder<Product>  $query */
    private function applySorting(Builder $query, Request $request): Builder
    {
        return match ($request->input('sort', 'latest')) {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'stock_asc' => $query->orderBy('stock'),
            'stock_desc' => $query->orderByDesc('stock'),
            default => $query->latest(),
        };
    }

    private function perPage(Request $request): int
    {
        return match ((int) $request->input('per_page', 20)) {
            50 => 50,
            100 => 100,
            default => 20,
        };
    }
}
