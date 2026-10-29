<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sdf-151-temiz-su-dalgic-pompa-monofaze-220v-15-hp';

    /** Trendyol PIM: Su Motoru ve Pompa (HMJ listing ile aynı). */
    private const TRENDYOL_CATEGORY_ID = '2600';

    private const TRENDYOL_CATEGORY_NAME = 'Su Motoru ve Pompa';

    public function up(): void
    {
        $product = Product::query()->with('categories')->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Ürün yok: '.self::SLUG);
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill([
                'marketplace_enabled' => true,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 12.00,
                'width_cm' => $product->width_cm ?: 25,
                'height_cm' => $product->height_cm ?: 45,
                'depth_cm' => $product->depth_cm ?: 25,
                'barcode' => filled($product->barcode) ? $product->barcode : 'SDF151',
            ])->save();
        });

        $categoryIds = $product->categories->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'temiz-su-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'dalgic-pompalar')->value('id');
            if ($fallback) {
                $product->categories()->syncWithoutDetaching([$fallback]);
                $categoryIds = collect([$fallback]);
            }
        }

        foreach ($categoryIds as $categoryId) {
            MarketplaceCategoryMapping::query()->updateOrCreate(
                [
                    'channel_key' => 'trendyol',
                    'category_id' => (int) $categoryId,
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
                'external_sku' => $product->sku ?: 'SDF151',
                'channel_price' => 23599.00,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        // Kategori eşlemesi diğer ürünlerde de kullanılabilir; geri alma yok.
    }
};
