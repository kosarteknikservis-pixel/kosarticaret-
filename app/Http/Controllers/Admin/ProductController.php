<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\RichContent;
use App\Support\SlugHelper;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with('brand')->latest()->paginate(20),
        ]);
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
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image_file')->store('products', 'public');
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
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $file->store('products/gallery', 'public'),
                'sort_order' => $sort,
            ]);
        }
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Galeri görseli silindi.');
    }
}
