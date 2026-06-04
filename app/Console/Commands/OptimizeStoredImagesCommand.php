<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Support\ImageVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeStoredImagesCommand extends Command
{
    protected $signature = 'images:optimize-stored
        {--force : Mevcut varyantları silip yeniden üret}
        {--shrink-originals : Büyük orijinal görselleri güvenli maksimum ölçülere küçült}';

    protected $description = 'Storage altındaki mevcut vitrin görselleri için optimize WebP varyantları üretir ve gerekirse orijinalleri küçültür.';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->warn('GD WebP desteği bulunamadı. Görsel optimizasyon atlandı.');

            return self::SUCCESS;
        }

        $items = $this->imageItems();
        $done = 0;
        $skipped = 0;
        $shrunk = 0;

        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $item) {
            $path = $item['path'];
            if (! is_string($path) || $path === '' || str_starts_with($path, 'http') || ! Storage::disk('public')->exists($path)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if ($this->option('force')) {
                ImageVariant::delete($path);
            }

            if ($this->option('shrink-originals') && ImageVariant::optimizeOriginal($path, $item['type'])) {
                $shrunk++;
                ImageVariant::delete($path);
            }

            ImageVariant::generate($path, ImageVariant::presetsFor($item['type']));
            $done++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Optimize edilen kaynak: {$done}");
        if ($this->option('shrink-originals')) {
            $this->info("Küçültülen orijinal: {$shrunk}");
        }
        if ($skipped > 0) {
            $this->line("Atlanan kaynak: {$skipped}");
        }

        return self::SUCCESS;
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
}
