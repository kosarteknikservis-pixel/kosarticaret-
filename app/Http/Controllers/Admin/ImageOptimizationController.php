<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Support\ImageVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ImageOptimizationController extends Controller
{
    public function index(): View
    {
        $items = $this->imageItems();
        $existing = collect($items)->filter(fn (array $item) => $this->isLocalExisting($item['path']));
        $variantReady = $existing->filter(function (array $item) {
            foreach (ImageVariant::presetsFor($item['type']) as $variant) {
                $variantPath = ImageVariant::path((string) $item['path'], $variant);
                if ($variantPath !== null && Storage::disk('public')->exists($variantPath)) {
                    return true;
                }
            }

            return false;
        })->count();

        return view('admin.performance.images', [
            'totalSources' => count($items),
            'existingSources' => $existing->count(),
            'variantReady' => $variantReady,
            'missingVariants' => max(0, $existing->count() - $variantReady),
            'webpSupported' => function_exists('imagewebp'),
        ]);
    }

    public function optimize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:variants,shrink'],
        ]);

        if (! function_exists('imagewebp')) {
            return back()->withErrors(['images' => 'Sunucuda GD WebP desteği bulunamadı. Hosting PHP GD/WebP desteğini açmalı.']);
        }

        @set_time_limit(0);

        $params = [];
        if ($data['mode'] === 'shrink') {
            $params['--shrink-originals'] = true;
            $params['--force'] = true;
        }

        Artisan::call('images:optimize-stored', $params);
        $output = trim(Artisan::output());

        return back()->with('success', $output !== '' ? $output : 'Görseller optimize edildi.');
    }

    /**
     * @return list<array{path:?string,type:string}>
     */
    private function imageItems(): array
    {
        $items = [];

        foreach (Product::query()->whereNotNull('image')->pluck('image') as $path) {
            $items[] = ['path' => $path, 'type' => 'product'];
        }

        foreach (ProductImage::query()->whereNotNull('path')->pluck('path') as $path) {
            $items[] = ['path' => $path, 'type' => 'product-gallery'];
        }

        foreach (Category::query()->whereNotNull('image')->pluck('image') as $path) {
            $items[] = ['path' => $path, 'type' => 'category'];
        }

        foreach (Brand::query()->whereNotNull('logo_url')->pluck('logo_url') as $path) {
            $items[] = ['path' => $path, 'type' => 'brand'];
        }

        foreach (HomeBanner::query()->whereNotNull('image')->pluck('image') as $path) {
            $items[] = ['path' => $path, 'type' => 'banner'];
        }

        foreach (BlogPost::query()->whereNotNull('image')->pluck('image') as $path) {
            $items[] = ['path' => $path, 'type' => 'blog'];
        }

        $logo = SiteSetting::get('site_logo');
        if ($logo) {
            $items[] = ['path' => $logo, 'type' => 'site-logo'];
        }

        return array_values(array_unique($items, SORT_REGULAR));
    }

    private function isLocalExisting(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_starts_with($path, 'http')
            && Storage::disk('public')->exists($path);
    }
}
