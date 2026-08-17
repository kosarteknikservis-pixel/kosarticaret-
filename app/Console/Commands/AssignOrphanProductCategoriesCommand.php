<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

class AssignOrphanProductCategoriesCommand extends Command
{
    protected $signature = 'seo:assign-orphan-categories';

    protected $description = 'Kategorisiz ürünleri sabit slug eşlemesiyle bağlar (canlı deploy).';

    /** @var array<string, string> */
    private const MAP = [
        'kaysu-sp750-a-plastik-su-pompasi-dalgic-temiz-su' => 'temiz-su-dalgic-pompasi',
        'kaysu-hcpf-70-tek-fanli-pompa' => 'tek-fanli-santrifuj-pompa',
        'kaysu-hmc145-6sh-yatay-milli-cok-kademeli-pompa' => 'yatay-kademeli-pompalar',
        'horoz-gama-80-watt-bant-armatur' => 'elektrik-ve-aydinlatma',
    ];

    public function handle(): int
    {
        $linked = 0;
        $skipped = 0;

        foreach (self::MAP as $productSlug => $categorySlug) {
            $product = Product::query()->where('slug', $productSlug)->first();
            $category = Category::query()->where('slug', $categorySlug)->first();

            if ($product === null || $category === null) {
                $this->warn("Atlandı (kayıt yok): {$productSlug} → {$categorySlug}");
                $skipped++;

                continue;
            }

            if ($product->categories()->where('categories.id', $category->id)->exists()) {
                $skipped++;

                continue;
            }

            $product->categories()->syncWithoutDetaching([$category->id]);
            $this->info("Bağlandı: {$productSlug} → {$categorySlug}");
            $linked++;
        }

        $this->info("Orphan kategori ataması: {$linked} bağlandı, {$skipped} atlandı.");

        return self::SUCCESS;
    }
}
