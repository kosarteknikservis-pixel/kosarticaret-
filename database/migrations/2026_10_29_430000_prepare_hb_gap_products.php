<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * TY'de olup HB'de olmayan ürünler: görsel/seed + marketplace hazırlığı.
 * 2HCP-160 zaten HB WAITING — dokunulmaz.
 */
return new class extends Migration
{
    /**
     * @var list<array{slug:string,sku:string,price:float,seed:string,target:string,weight:float}>
     */
    private const PRODUCTS = [
        [
            'slug' => 'kaysu-pompa-hidrofor-050-hp-1-kat-1-daire',
            'sku' => 'Kaysu HKJMH',
            'price' => 5625.00,
            'seed' => 'kaysu/hkjmh-1.jpg',
            'target' => 'products/kaysu/hkjmh-1.jpg',
            'weight' => 18.00,
        ],
        [
            'slug' => 'kaysu-pompa-hkjmh-10h-jet-hidrofor-4-kat-4-daire-24l-tankli',
            'sku' => 'Kaysu HKJM',
            'price' => 10125.00,
            'seed' => 'kaysu/hkjm-1.jpg',
            'target' => 'products/kaysu/hkjm-1.jpg',
            'weight' => 22.00,
        ],
        [
            'slug' => 'sumak-smj-150-jet-paket-hidrofor-5-kat-10-daire-24-litre-tankli',
            'sku' => 'SMJH-150-24',
            'price' => 25313.00,
            'seed' => 'sumak/smj150-24-1.jpg',
            'target' => 'products/sumak/smj150-24-1.jpg',
            'weight' => 24.00,
        ],
        [
            'slug' => 'winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v',
            'sku' => 'WNP400AW',
            'price' => 5599.00,
            'seed' => 'winpo/wnp400aw-1.jpg',
            'target' => 'products/winpo/wnp400aw-1.jpg',
            'weight' => 8.00,
        ],
    ];

    public function up(): void
    {
        foreach (self::PRODUCTS as $row) {
            $product = Product::query()->where('slug', $row['slug'])->first();
            if ($product === null) {
                continue;
            }

            $seed = database_path('seed-assets/'.$row['seed']);
            $target = $row['target'];
            if (is_file($seed)) {
                Storage::disk('public')->put($target, (string) file_get_contents($seed));
            }

            Product::withoutEvents(function () use ($product, $row, $target, $seed): void {
                $product->forceFill([
                    'sku' => filled($product->sku) ? $product->sku : $row['sku'],
                    'barcode' => filled($product->barcode) ? $product->barcode : preg_replace('/\s+/', '', $row['sku']),
                    'marketplace_enabled' => true,
                    'is_active' => true,
                    'stock' => max(1, (int) $product->stock),
                    'weight_kg' => ($product->weight_kg !== null && (float) $product->weight_kg > 0)
                        ? $product->weight_kg
                        : $row['weight'],
                    'width_cm' => $product->width_cm ?: 30,
                    'height_cm' => $product->height_cm ?: 40,
                    'depth_cm' => $product->depth_cm ?: 30,
                    'vat_rate' => $product->vat_rate ?: config('marketplace.default_vat_rate', 20),
                    'image' => is_file($seed) ? $target : $product->image,
                ])->save();
            });

            if (is_file($seed)) {
                ProductImage::query()->where('product_id', $product->id)->delete();
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $target,
                    'alt' => $product->name,
                    'sort_order' => 0,
                ]);
            }

            MarketplaceListing::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'channel_key' => 'hepsiburada',
                ],
                [
                    'status' => 'draft',
                    'external_sku' => strtoupper((string) ($product->fresh()->sku ?: $row['sku'])),
                    'channel_price' => $row['price'],
                    'external_product_id' => null,
                    'last_error' => null,
                ],
            );
        }
    }

    public function down(): void
    {
        // no-op
    }
};
