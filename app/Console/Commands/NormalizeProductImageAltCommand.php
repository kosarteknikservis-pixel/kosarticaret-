<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductImageAlt;
use Illuminate\Console\Command;

class NormalizeProductImageAltCommand extends Command
{
    protected $signature = 'products:normalize-image-alt
                            {--dry-run : Sadece rapor}
                            {--limit= : Maksimum güncelleme sayısı}';

    protected $description = 'Zayıf veya boş ürün image_alt alanlarını SEO şablonuna göre doldurur';

    public function handle(): int
    {
        $updated = 0;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        Product::query()
            ->active()
            ->with('brand:id,name')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($products) use (&$updated, $limit): bool {
                foreach ($products as $product) {
                    if ($limit !== null && $updated >= $limit) {
                        return false;
                    }

                    if (! ProductImageAlt::needsNormalization($product->image_alt, $product->name)) {
                        continue;
                    }

                    $alt = ProductImageAlt::generate($product->name, $product->brand?->name);

                    if ($this->option('dry-run')) {
                        $this->line("{$product->sku} | {$product->image_alt} → {$alt}");
                    } else {
                        $product->update(['image_alt' => $alt]);
                    }

                    $updated++;
                }

                return true;
            });

        $this->info($this->option('dry-run')
            ? "Güncellenecek: {$updated} ürün."
            : "Güncellendi: {$updated} ürün.");

        return self::SUCCESS;
    }
}
