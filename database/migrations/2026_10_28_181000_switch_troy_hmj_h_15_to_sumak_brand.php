<?php

use App\Models\Brand;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli';

    public function up(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Ürün yok: '.self::SLUG);
        }

        $sumak = Brand::query()->where('slug', 'sumak')->first();
        if ($sumak === null) {
            throw new \RuntimeException('Sumak markası yok.');
        }

        Product::withoutEvents(function () use ($product, $sumak): void {
            $product->fill([
                'brand_id' => $sumak->id,
            ])->save();
        });

        // Eski Troy→Trendyol eşlemesi bu ürün için kullanılmasın; listing yeniden gönderime açılsın.
        MarketplaceListing::query()
            ->where('product_id', $product->id)
            ->where('channel_key', 'trendyol')
            ->update([
                'status' => 'draft',
                'external_product_id' => null,
                'last_error' => null,
            ]);
    }

    public function down(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        $troy = Brand::query()->where('slug', 'troy')->first();
        if ($product === null || $troy === null) {
            return;
        }

        Product::withoutEvents(function () use ($product, $troy): void {
            $product->fill(['brand_id' => $troy->id])->save();
        });
    }
};
