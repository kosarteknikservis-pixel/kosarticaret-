<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Hepsiburada kuyruk: oran/çözünürlük uymayan veya eksik görselleri
 * seed'den kare JPG/PNG ile değiştirir (HB min 400px, oran ≤1.35).
 */
return new class extends Migration
{
    /**
     * @var list<array{slug:string,seed:string,target:string}>
     */
    private const ITEMS = [
        [
            'slug' => 'kaysu-hwd-1100s-paslanmaz-govdeli-kirli-su-dalgic-pompasi-15-hp-220-volt',
            'seed' => 'seed-assets/kaysu/hwd-1100s.jpg',
            'target' => 'products/import/hwd-1100s.jpg',
        ],
        [
            'slug' => 'sumak-smjk-100-kendinden-emisli-jet-pompa-monofaze-220v-1hp',
            'seed' => 'seed-assets/sumak/smjk100.jpg',
            'target' => 'products/import/smjk100.jpg',
        ],
        [
            'slug' => 'pedrollo-bc-1550n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa',
            'seed' => 'seed-assets/pedrollo/bc1550n.jpg',
            'target' => 'products/import/bc1550n.jpg',
        ],
        [
            'slug' => 'pedrollo-bcm-1050n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa',
            'seed' => 'seed-assets/pedrollo/bcm1050n.jpg',
            'target' => 'products/import/bcm1050n.jpg',
        ],
        [
            'slug' => 'sumak-sm-10-preferikal-pompa-monofaze-220v-1hp',
            'seed' => 'seed-assets/sumak/sm10.jpg',
            'target' => 'products/import/sm10.jpg',
        ],
        [
            'slug' => 'sumak-smj-220-jet-paket-hidrofor-8-kat-16-daire-50-litre-tankli',
            'seed' => 'seed-assets/sumak/smj220-1.jpg',
            'target' => 'products/sumak/smj220-1.jpg',
        ],
        [
            'slug' => 'kaysu-sp100050-kapali-fanli-plastik-drenaj-dalgic-su-pompasi',
            'seed' => 'seed-assets/kaysu/51.jpg',
            'target' => 'products/import/51.jpg',
        ],
        [
            'slug' => 'kaysu-hqbm60-preferikal-surtme-fanli-su-pompasi',
            'seed' => 'seed-assets/kaysu/24.jpg',
            'target' => 'products/import/24.jpg',
        ],
    ];

    public function up(): void
    {
        foreach (self::ITEMS as $item) {
            $this->applyImage($item['slug'], $item['seed'], $item['target']);
        }
    }

    public function down(): void
    {
        // Görsel geri alınmaz.
    }

    private function applyImage(string $slug, string $seedRelative, string $target): void
    {
        $product = Product::query()->where('slug', $slug)->first();
        if ($product === null) {
            return;
        }

        $source = database_path($seedRelative);
        if (! is_file($source)) {
            throw new \RuntimeException('HB görsel seed yok: '.$source);
        }

        Storage::disk('public')->put($target, (string) file_get_contents($source));

        Product::withoutEvents(function () use ($product, $target): void {
            $product->forceFill([
                'image' => $target,
                'image_alt' => $product->image_alt ?: $product->name,
            ])->save();
        });

        ProductImage::query()->where('product_id', $product->id)->delete();
        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $target,
            'alt' => $product->name,
            'sort_order' => 0,
        ]);
    }
};
