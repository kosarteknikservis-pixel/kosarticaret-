<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearCatalogCommand extends Command
{
    protected $signature = 'catalog:clear
                            {--force : Onay sormadan sil}
                            {--keep-orders : Sipariş kayıtlarını koru}';

    protected $description = 'Ürün, kategori, marka ve vitrin bağlantılarını siler (yeniden import öncesi)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Tüm katalog verisi silinecek (ürün, kategori, marka, yorumlar, ürün görselleri). Devam?', false)) {
            return self::SUCCESS;
        }

        $stats = [
            'reviews' => 0,
            'images' => 0,
            'products' => 0,
            'categories' => 0,
            'brands' => 0,
            'banners' => 0,
            'orders' => 0,
            'files' => 0,
        ];

        DB::transaction(function () use (&$stats) {
            $stats['reviews'] = ProductReview::query()->count();
            ProductReview::query()->delete();

            $imagePaths = ProductImage::query()->pluck('path')
                ->merge(Product::query()->whereNotNull('image')->pluck('image'))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $stats['images'] = ProductImage::query()->count();
            ProductImage::query()->delete();

            $categoryPaths = Category::query()->whereNotNull('image')->pluck('image')->filter()->all();
            $brandPaths = Brand::query()->whereNotNull('logo_url')->pluck('logo_url')->filter()->all();

            $stats['banners'] = HomeBanner::query()
                ->whereIn('type', [
                    HomeBanner::TYPE_PRODUCT,
                    HomeBanner::TYPE_CATEGORY,
                    HomeBanner::TYPE_PRODUCT_LIST,
                ])
                ->count();
            HomeBanner::query()
                ->whereIn('type', [
                    HomeBanner::TYPE_PRODUCT,
                    HomeBanner::TYPE_CATEGORY,
                    HomeBanner::TYPE_PRODUCT_LIST,
                ])
                ->delete();

            DB::table('home_banners')->update([
                'product_id' => null,
                'category_id' => null,
                'product_source' => null,
                'brand_id' => null,
                'product_ids' => null,
            ]);

            DB::table('category_product')->delete();

            $stats['products'] = Product::query()->count();
            Product::query()->delete();

            while (Category::query()->whereNotNull('parent_id')->exists()) {
                Category::query()->whereDoesntHave('children')->delete();
            }
            $stats['categories'] = Category::query()->count();
            Category::query()->delete();

            $stats['brands'] = Brand::query()->count();
            Brand::query()->delete();

            if (! $this->option('keep-orders')) {
                $stats['orders'] = Order::query()->count();
                OrderItem::query()->delete();
                Order::query()->delete();
            }

            $stats['files'] = $this->purgeStoragePaths(array_merge(
                $imagePaths,
                $categoryPaths,
                $brandPaths
            ));
            $stats['files'] += $this->purgeStorageDirectory('products');
        });

        $this->newLine();
        $this->info('Katalog sıfırlandı.');
        $this->table(
            ['Alan', 'Silinen'],
            [
                ['Ürün', (string) $stats['products']],
                ['Kategori', (string) $stats['categories']],
                ['Marka', (string) $stats['brands']],
                ['Yorum', (string) $stats['reviews']],
                ['Galeri kaydı', (string) $stats['images']],
                ['Ürün/kategori vitrin bloğu', (string) $stats['banners']],
                ['Sipariş', $this->option('keep-orders') ? 'korundu' : (string) $stats['orders']],
                ['Dosya (storage)', (string) $stats['files']],
            ]
        );
        $this->newLine();
        $this->comment('Sonraki adım: php artisan catalog:import-woocommerce --file=DOSYA.xlsx --force');

        return self::SUCCESS;
    }

    /** @param list<string|null> $paths */
    private function purgeStoragePaths(array $paths): int
    {
        $deleted = 0;
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                continue;
            }
            if (Storage::disk('public')->delete($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function purgeStorageDirectory(string $directory): int
    {
        $full = storage_path('app/public/'.$directory);
        if (! is_dir($full)) {
            return 0;
        }

        $count = 0;
        foreach (File::allFiles($full) as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        File::deleteDirectory($full);
        Storage::disk('public')->makeDirectory($directory);

        return $count;
    }
}
