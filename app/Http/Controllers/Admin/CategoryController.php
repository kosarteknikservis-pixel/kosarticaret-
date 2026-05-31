<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\RichContent;
use App\Support\SlugHelper;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->with('parent')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category,
            'parents' => Category::query()->whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::query()->create($this->validated($request, null));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori eklendi.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::query()->whereNull('parent_id')->where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori güncellendi.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->withErrors(['name' => 'Alt kategorisi olan kayıt silinemez.']);
        }
        if (Product::query()->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))->exists()) {
            return back()->withErrors(['name' => 'Bu kategoride ürün var.']);
        }

        if ($category->image && ! str_starts_with($category->image, 'http')) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori silindi.');
    }

    private function validated(Request $request, ?Category $category): array
    {
        $request->merge([
            'parent_id' => $request->input('parent_id') ?: null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'faq' => ['nullable', 'array'],
            'faq.*.q' => ['nullable', 'string', 'max:500'],
            'faq.*.a' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['slug'] = SlugHelper::assign('categories', $data['slug'] ?? null, $data['name'], $category?->id);
        $data['featured'] = $request->boolean('featured');
        $data['show_in_menu'] = $request->boolean('show_in_menu', true);
        $data['active'] = $request->boolean('active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->boolean('remove_image') && $category?->image) {
            if (! str_starts_with($category->image, 'http')) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = null;
        } elseif ($request->hasFile('image_file')) {
            if ($category?->image && ! str_starts_with($category->image, 'http')) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image_file')->store('categories', 'public');
        }

        unset($data['image_file']);

        $data['description'] = RichContent::normalize($data['description'] ?? null);

        // Clean FAQ: remove items with empty question
        $rawFaq = $data['faq'] ?? [];
        $data['faq'] = array_values(
            array_filter($rawFaq, fn ($item) => filled($item['q'] ?? null))
        ) ?: null;

        return $data;
    }
}
