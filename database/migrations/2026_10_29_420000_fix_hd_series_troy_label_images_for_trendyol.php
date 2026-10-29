<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * HD500 / HD1300-2 / HD1700-2 görsellerinde TROY etiket Trendyol marka (Sumak)
 * uyumsuzluğu yaratıyordu. Etiketsiz seed ile ana görsel yenilenir; grafik 2. görsel kalkar.
 */
return new class extends Migration
{
    /**
     * @var list<array{slug:string,seed:string,target:string,price:float}>
     */
    private const ITEMS = [
        [
            'slug' => 'sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt',
            'seed' => 'seed-assets/sumak/hd500-1.jpg',
            'target' => 'products/sumak/hd500-1.jpg',
            'price' => 3999.0,
        ],
        [
            'slug' => 'sumak-hd-1300-2-flatorlu-kirli-su-foseptik-dalgic-pompa-1-hp',
            'seed' => 'seed-assets/sumak/hd1300-2-1.jpg',
            'target' => 'products/sumak/hd1300-2-1.jpg',
            'price' => 11199.0,
        ],
        [
            'slug' => 'sumak-hd-1700-2-flatorlu-kirli-su-foseptik-dalgic-pompa-15-hp',
            'seed' => 'seed-assets/sumak/hd1700-2-1.jpg',
            'target' => 'products/sumak/hd1700-2-1.jpg',
            'price' => 12599.0,
        ],
    ];

    public function up(): void
    {
        foreach (self::ITEMS as $item) {
            $this->apply($item['slug'], $item['seed'], $item['target'], $item['price']);
        }
    }

    public function down(): void
    {
        // Görsel geri alınmaz.
    }

    private function apply(string $slug, string $seedRelative, string $target, float $price): void
    {
        $product = Product::query()->where('slug', $slug)->first();
        if ($product === null) {
            return;
        }

        $source = database_path($seedRelative);
        if (! is_file($source)) {
            throw new \RuntimeException('Seed yok: '.$source);
        }

        Storage::disk('public')->put($target, (string) file_get_contents($source));

        Product::withoutEvents(function () use ($product, $target): void {
            $product->forceFill([
                'image' => $target,
                'image_alt' => $product->image_alt ?: $product->name,
            ])->save();
        });

        // Grafik / ikinci görseller Trendyol marka kontrolünü bozabiliyor; yalnız ana foto.
        ProductImage::query()->where('product_id', $product->id)->delete();
        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $target,
            'alt' => $product->name,
            'sort_order' => 0,
        ]);

        MarketplaceListing::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'channel_key' => 'trendyol',
            ],
            [
                'status' => 'draft',
                'external_sku' => (string) ($product->sku ?: 'HD'),
                'channel_price' => $price,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }
};
