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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ImageOptimizationController extends Controller
{
    private const VARIANT_BATCH_SIZE = 8;
    private const SHRINK_BATCH_SIZE = 3;

    public function index(): View
    {
        $items = $this->imageItems();
        $existing = collect($items)->filter(fn (array $item) => $this->isLocalExisting($item['path']));
        $variantReady = $existing->filter(function (array $item) {
            return $this->allVariantsExist((string) $item['path'], $item['type']);
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

        @set_time_limit(45);

        $batchSize = $data['mode'] === 'shrink' ? self::SHRINK_BATCH_SIZE : self::VARIANT_BATCH_SIZE;
        $processed = 0;
        $shrunk = 0;
        $skipped = 0;
        $remaining = 0;

        foreach ($this->imageItems() as $item) {
            $path = $item['path'];
            if (! $this->isLocalExisting($path)) {
                $skipped++;

                continue;
            }

            $path = (string) $path;
            $needsVariants = ! $this->allVariantsExist($path, $item['type']);
            $needsOriginalShrink = $data['mode'] === 'shrink'
                && ImageVariant::originalNeedsOptimization($path, $item['type']);
            $needsWork = $needsOriginalShrink || $needsVariants;

            if (! $needsWork) {
                continue;
            }

            if ($processed >= $batchSize) {
                $remaining++;

                continue;
            }

            if (! $this->safeForWebProcessing($path)) {
                $skipped++;

                continue;
            }

            try {
                if ($needsOriginalShrink && ImageVariant::optimizeOriginal($path, $item['type'])) {
                    $shrunk++;
                    ImageVariant::delete($path);
                }

                ImageVariant::generate($path, ImageVariant::presetsFor($item['type']));
                $processed++;
            } catch (Throwable) {
                $skipped++;
            }
        }

        $message = "İşlenen görsel: {$processed}";
        if ($data['mode'] === 'shrink') {
            $message .= " · Küçültülen orijinal: {$shrunk}";
        }
        if ($skipped > 0) {
            $message .= " · Atlanan: {$skipped}";
        }
        if ($remaining > 0) {
            $message .= " · Kalan işlem var, lütfen butona tekrar basın.";
        } else {
            $message .= " · Bu tur tamamlandı.";
        }

        return back()->with('success', $message);
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

    private function allVariantsExist(string $path, string $type): bool
    {
        $variants = ImageVariant::presetsFor($type);
        if ($variants === []) {
            return false;
        }

        foreach ($variants as $variant) {
            $variantPath = ImageVariant::path($path, $variant);
            if ($variantPath === null || ! Storage::disk('public')->exists($variantPath)) {
                return false;
            }
        }

        return true;
    }

    private function safeForWebProcessing(string $path): bool
    {
        $absolutePath = Storage::disk('public')->path($path);
        $size = @getimagesize($absolutePath);
        if (! is_array($size)) {
            return false;
        }

        $width = (int) ($size[0] ?? 0);
        $height = (int) ($size[1] ?? 0);
        if ($width < 1 || $height < 1) {
            return false;
        }

        $memoryLimit = $this->memoryLimitBytes();
        if ($memoryLimit === null) {
            return true;
        }

        $estimatedBytes = ($width * $height * 8) + (32 * 1024 * 1024);

        return $estimatedBytes < ($memoryLimit * 0.65);
    }

    private function memoryLimitBytes(): ?int
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') {
            return null;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
