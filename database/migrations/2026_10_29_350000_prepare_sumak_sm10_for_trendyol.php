<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sm-10-preferikal-pompa-monofaze-220v-1hp';

    private const TRENDYOL_PRICE = 13922.00;

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
            $sku = filled($product->sku) && ! in_array((string) $product->sku, ['51', '24', ''], true)
                ? $product->sku
                : 'SM10';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) && ! in_array((string) $product->barcode, ['51', '24', ''], true)
                    ? $product->barcode
                    : 'SM10',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 8.00,
                'width_cm' => $product->width_cm ?: 20,
                'height_cm' => $product->height_cm ?: 25,
                'depth_cm' => $product->depth_cm ?: 32,
                'short_description' => 'Sumak SM 10 preferikal pompa: 1 HP (0.75 kW), 220 V. Emiş 6 m, maks. 65 mss. Giriş/çıkış 1", 2900 d/d.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SM 10',
                    'Tip' => 'Preferikal (sürtme fanlı) yüzey tipi pompa',
                    'Motor Gücü' => '1 HP (0.75 kW)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Giriş / Çıkış Ölçüsü' => '1" (25 mm)',
                    'Emiş Derinliği' => '6 m',
                    'Maks. Basma Yüksekliği' => '65 m',
                    'Devir' => '2900 d/d',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'preferikal-pompalar-surtme-fanli')->value('id')
                ?: Category::query()->where('slug', 'su-pompalari')->value('id');
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
                'external_sku' => $product->fresh()->sku ?: 'SM10',
                'channel_price' => self::TRENDYOL_PRICE,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        $product = Product::query()->where('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        Product::withoutEvents(function () use ($product): void {
            $product->fill(['marketplace_enabled' => false])->save();
        });
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak SM 10 Preferikal Pompa Monofaze (220V) 1HP</h2>
<p>Sumak SM 10, 1 HP (0.75 kW) monofaze preferikal (sürtme fanlı) yüzey pompasıdır. Tarımsal sulama, az kirli su tahliyesi ve bina su temini uygulamalarında kullanılır. Emiş derinliği 6 m, maksimum basma yüksekliği 65 m’dir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SM 10</td></tr>
<tr><td>Motor gücü</td><td>1 HP (0.75 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Giriş / çıkış</td><td>1" (25 mm)</td></tr>
<tr><td>Emiş derinliği</td><td>6 m</td></tr>
<tr><td>Maks. basma</td><td>65 m</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Tarımsal damlama / salma sulama</li>
<li>Bahçe ve küçük tarla sulaması</li>
<li>Az kirli su birikinti tahliyesi</li>
<li>Bina su temini</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yüzey tipi preferikal pompadır. Emiş hattında çekvalf kullanın; priming yapın. Klape ve pislik tutucu önerilir. Yalnızca 220 V monofaze besleme ile çalışır.</p>
HTML;
    }
};
