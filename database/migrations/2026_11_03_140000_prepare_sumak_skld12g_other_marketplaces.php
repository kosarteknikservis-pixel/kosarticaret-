<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * SKLD12G: Hepsiburada / N11 / Idefix / PttAVM draft listing (2455 TL).
 * Site ve Trendyol fiyatı değişmez. Marka: ürün markası (Sumak).
 */
return new class extends Migration
{
    private const SLUG = 'sumak-skld12g-kalem-dalgic-pompasi-3-mss-720-litre-h-12-volt-manuel';

    private const PRICE = 2455.00;

    private const BARCODE = '2000000120126';

    /** @var list<string> */
    private const CHANNELS = ['hepsiburada', 'n11', 'idefix', 'pttavm'];

    /** @var list<string> */
    private const FALLBACK_CATEGORY_SLUGS = [
        'sintine-pompasi',
        'dalgic-pompalar',
        'temiz-su-dalgic-pompasi',
        'su-pompalari',
    ];

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

        $product = $product->fresh(['categories']);
        $categoryIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'sintine-pompasi')->value('id')
                ?: Category::query()->where('slug', 'dalgic-pompalar')->value('id');
            if ($fallback) {
                $product->categories()->syncWithoutDetaching([(int) $fallback]);
                $categoryIds = collect([(int) $fallback]);
            }
        }

        foreach (self::CHANNELS as $channelKey) {
            $this->ensureCategoryMappings($channelKey, $categoryIds->all());

            MarketplaceListing::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'channel_key' => $channelKey,
                ],
                [
                    'status' => 'draft',
                    'external_sku' => $product->sku ?: 'SKLD12G',
                    'external_barcode' => self::BARCODE,
                    'channel_price' => self::PRICE,
                    'channel_stock_limit' => 5,
                    'external_product_id' => null,
                    'last_error' => null,
                ],
            );
        }
    }

    public function down(): void
    {
        // Listing geri alınmaz.
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function ensureCategoryMappings(string $channelKey, array $categoryIds): void
    {
        $donor = null;
        foreach ($categoryIds as $categoryId) {
            $existing = MarketplaceCategoryMapping::query()
                ->where('channel_key', $channelKey)
                ->where('category_id', $categoryId)
                ->whereNotNull('external_category_id')
                ->first();
            if ($existing !== null) {
                $donor = $existing;
                break;
            }
        }

        if ($donor === null) {
            $fallbackIds = Category::query()
                ->whereIn('slug', self::FALLBACK_CATEGORY_SLUGS)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($fallbackIds as $fallbackId) {
                $donor = MarketplaceCategoryMapping::query()
                    ->where('channel_key', $channelKey)
                    ->where('category_id', $fallbackId)
                    ->whereNotNull('external_category_id')
                    ->first();
                if ($donor !== null) {
                    break;
                }
            }
        }

        if ($donor === null) {
            $donor = MarketplaceCategoryMapping::query()
                ->where('channel_key', $channelKey)
                ->whereNotNull('external_category_id')
                ->where('external_category_id', '!=', '')
                ->first();
        }

        if ($donor === null) {
            return;
        }

        foreach ($categoryIds as $categoryId) {
            MarketplaceCategoryMapping::query()->updateOrCreate(
                [
                    'channel_key' => $channelKey,
                    'category_id' => $categoryId,
                ],
                [
                    'external_category_id' => (string) $donor->external_category_id,
                    'external_category_name' => $donor->external_category_name,
                ],
            );
        }
    }
};
