<?php

use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Canlı slug: sumak-sdf152-...-15-hp (eski migration farklı slug kullandı).
 * HB için barkod + desi + görsel hazırlığı.
 */
return new class extends Migration
{
    private const SLUG = 'sumak-sdf152-komple-paslanmaz-foseptik-dalgic-drenaj-pompa-220-volt-15-hp';

    private const PRICE = 26999.00;

    private const TRENDYOL_CATEGORY_ID = '2600';

    private const TRENDYOL_CATEGORY_NAME = 'Su Motoru ve Pompa';

    public function up(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        $seed = database_path('seed-assets/sumak/sdf15-2-1.jpg');
        $target = 'products/sumak/sdf15-2-1.jpg';
        if (is_file($seed)) {
            Storage::disk('public')->put($target, (string) file_get_contents($seed));
        }

        Product::withoutEvents(function () use ($product, $target): void {
            $product->forceFill([
                'sku' => filled($product->sku) ? $product->sku : 'SDF152',
                'barcode' => filled($product->barcode) ? $product->barcode : 'SDF152',
                'marketplace_enabled' => true,
                'stock' => max(1, (int) $product->stock),
                'weight_kg' => ($product->weight_kg !== null && (float) $product->weight_kg > 0)
                    ? $product->weight_kg
                    : 16.00,
                'width_cm' => $product->width_cm ?: 19,
                'height_cm' => $product->height_cm ?: 40,
                'depth_cm' => $product->depth_cm ?: 22,
                'vat_rate' => $product->vat_rate ?: config('marketplace.default_vat_rate', 20),
                'image' => is_file(database_path('seed-assets/sumak/sdf15-2-1.jpg'))
                    ? $target
                    : $product->image,
            ])->save();
        });

        if (is_file(database_path('seed-assets/sumak/sdf15-2-1.jpg'))) {
            ProductImage::query()->where('product_id', $product->id)->delete();
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $target,
                'alt' => $product->name,
                'sort_order' => 0,
            ]);
        }

        $categoryId = $product->categories()->orderBy('categories.id')->value('categories.id');
        if ($categoryId) {
            MarketplaceCategoryMapping::query()->updateOrCreate(
                [
                    'channel_key' => 'trendyol',
                    'category_id' => $categoryId,
                ],
                [
                    'external_category_id' => self::TRENDYOL_CATEGORY_ID,
                    'external_category_name' => self::TRENDYOL_CATEGORY_NAME,
                ],
            );
        }

        MarketplaceListing::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'channel_key' => 'trendyol',
            ],
            [
                'status' => 'draft',
                'external_sku' => 'SDF152',
                'channel_price' => self::PRICE,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        // no-op
    }
};
