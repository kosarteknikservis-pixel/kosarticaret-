<?php

use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * R-3001: error durumundaki listing’i fiyat güncellemesi için toparla.
 */
return new class extends Migration
{
    private const SLUG = 'renato-r-3001-2500w-kumandali-dikey-karbon-isitici-6-kademe-termostatli';

    private const TRENDYOL_PRICE = 8199.00;

    public function up(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first()
            ?? Product::query()->where('sku', 'R-3001')->first();

        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('R-3001 ürünü yok.');
        }

        $listing = MarketplaceListing::query()->firstOrNew([
            'product_id' => $product->id,
            'channel_key' => 'trendyol',
        ]);

        if (in_array((string) $listing->status, ['error', 'draft', ''], true) || $listing->status === null) {
            $listing->status = 'published';
        }

        $listing->fill([
            'channel_price' => self::TRENDYOL_PRICE,
            'external_sku' => $product->sku ?: 'R-3001',
            'external_barcode' => filled($listing->external_barcode)
                ? $listing->external_barcode
                : ($product->barcode ?: null),
            'last_error' => null,
        ])->save();
    }

    public function down(): void
    {
        // no-op
    }
};
