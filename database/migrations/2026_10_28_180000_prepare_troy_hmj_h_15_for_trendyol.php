<?php

use App\Models\MarketplaceChannel;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SLUG = 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli';

    public function up(): void
    {
        if (! Schema::hasColumn('products', 'cost_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->nullable()->after('price');
            });
        }

        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Troy HMJ H 15 ürünü yok: '.self::SLUG);
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill([
                'cost_price' => 6800.00,
                'marketplace_enabled' => true,
                'is_active' => true,
                // Satıcı stok barkodu (üretici EAN yoksa Trendyol’da benzersiz kod).
                'barcode' => filled($product->barcode) ? $product->barcode : 'HMJH1524L',
            ])->save();
        });

        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel === null) {
            return;
        }

        $settings = $channel->settings ?? [];
        $settings['price_mode'] = 'margin';
        $settings['commission_percent'] = $settings['commission_percent'] ?? 15;
        $settings['other_fee_percent'] = $settings['other_fee_percent'] ?? 0;
        $settings['target_margin_percent'] = $settings['target_margin_percent'] ?? 10;
        $settings['fixed_fee'] = $settings['fixed_fee'] ?? 0;

        $channel->update(['settings' => $settings]);
    }

    public function down(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill([
                'marketplace_enabled' => false,
            ])->save();
        });
    }
};
