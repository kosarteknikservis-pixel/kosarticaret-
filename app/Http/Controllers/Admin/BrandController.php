<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\ImageVariant;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand]);
    }

    public function store(Request $request): RedirectResponse
    {
        Brand::query()->create($this->validated($request));

        return redirect()->route('admin.brands.index')->with('success', 'Marka eklendi.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', ['brand' => $brand]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->validated($request, $brand));

        return redirect()->route('admin.brands.index')->with('success', 'Marka güncellendi.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->logo_url && ! str_starts_with($brand->logo_url, 'http')) {
            ImageVariant::delete($brand->logo_url);
            Storage::disk('public')->delete($brand->logo_url);
        }
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Marka silindi.');
    }

    private function validated(Request $request, ?Brand $brand): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
        ]);

        $data['slug'] = SlugHelper::assign('brands', $data['slug'] ?? null, $data['name'], $brand?->id);
        $data['featured'] = $request->boolean('featured');
        $data['active'] = $request->boolean('active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->boolean('remove_logo') && $brand?->logo_url) {
            if (! str_starts_with($brand->logo_url, 'http')) {
                ImageVariant::delete($brand->logo_url);
                Storage::disk('public')->delete($brand->logo_url);
            }
            $data['logo_url'] = null;
        } elseif ($request->hasFile('logo_file')) {
            if ($brand?->logo_url && ! str_starts_with($brand->logo_url, 'http')) {
                ImageVariant::delete($brand->logo_url);
                Storage::disk('public')->delete($brand->logo_url);
            }
            $data['logo_url'] = $request->file('logo_file')->store('brands', 'public');
            ImageVariant::generate($data['logo_url'], ImageVariant::presetsFor('brand'));
        } elseif (! $request->filled('logo_url') && $brand) {
            unset($data['logo_url']);
        }

        unset($data['logo_file']);

        $data['description'] = RichContent::normalize($data['description'] ?? null);

        return $data;
    }
}
