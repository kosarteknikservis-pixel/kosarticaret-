<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * SKLD12G Trendyol gönderimi: 2455 TL, Koşar marka (kanal force_brand).
 * Site fiyatı 1999 TL kalır.
 */
return new class extends Migration
{
    private const SLUG = 'sumak-skld12g-kalem-dalgic-pompasi-3-mss-720-litre-h-12-volt-manuel';

    private const TRENDYOL_PRICE = 2455.00;

    private const TRENDYOL_BARCODE = '2000000120126';

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
                'is_active' => true,
                'sku' => filled($product->sku) ? $product->sku : 'SKLD12G',
                'barcode' => filled($product->barcode) ? $product->barcode : 'SKLD12G',
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'sintine-pompasi')->value('id')
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

        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel !== null) {
            $settings = is_array($channel->settings) ? $channel->settings : [];
            if (blank($settings['force_brand_external_id'] ?? null)) {
                // Önceki toplu Koşar zorlaması yoksa en azından isim bayrağını koy;
                // publish öncesi artisan force-brand --skip-publish id’yi doldurur.
                $settings['force_brand_external_name'] = $settings['force_brand_external_name'] ?? 'Koşar';
                $channel->settings = $settings;
                $channel->save();
            }
        }

        MarketplaceListing::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'channel_key' => 'trendyol',
            ],
            [
                'status' => 'draft',
                'external_sku' => $product->sku ?: 'SKLD12G',
                'external_barcode' => self::TRENDYOL_BARCODE,
                'channel_price' => self::TRENDYOL_PRICE,
                'channel_stock_limit' => 5,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        // Listing ve eşleştirme geri alınmaz.
    }
};
