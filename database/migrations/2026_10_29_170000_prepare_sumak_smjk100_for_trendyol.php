<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-smjk-100-kendinden-emisli-jet-pompa-monofaze-220v-1hp';

    private const TRENDYOL_PRICE = 13599.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'SMJK100',
                'sku' => filled($product->sku) ? $product->sku : 'SMJK100',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 12.00,
                'width_cm' => $product->width_cm ?: 25,
                'height_cm' => $product->height_cm ?: 30,
                'depth_cm' => $product->depth_cm ?: 40,
                'short_description' => 'Sumak SMJK 100 kendinden emişli jet pompa: 1 HP (0,75 kW), 220 V monofaze. Maks. 41–50 m, 0–5 m³/h, 7 m emiş.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Motor Gücü' => '1 HP (0.75 kW)',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Maks. Debi' => '0-5 m3/h',
                    'Maks. Basma Yüksekliği' => '41-50 m',
                    'Giriş Ölçüsü' => '1" - 25 mm',
                    'Çıkış Ölçüsü' => '1" - 25 mm',
                    'Devir' => '2900 d/d',
                    'Emiş Derinliği' => '7 Metre',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'jet-pompalar-derinden-emisli')->value('id')
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
                'external_sku' => $product->sku ?: 'SMJK100',
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
<h2>Sumak SMJK 100 Kendinden Emişli Jet Pompa Monofaze 220V 1HP</h2>
<p>Sumak SMJK 100, 1 HP (0,75 kW) monofaze kendinden emişli jet pompadır. Depo veya sığ kuyudan temiz su emişi ve basma için kullanılır. Tanklı paket hidrofor değildir; yalnız pompa gövdesidir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SMJK 100</td></tr>
<tr><td>Motor gücü</td><td>1 HP (0,75 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>41–50 m</td></tr>
<tr><td>Giriş / çıkış</td><td>1" – 1" (25 mm)</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Emiş derinliği</td><td>7 metre</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Depodan temiz su emişi</li>
<li>Bahçe sulama destek hattı</li>
<li>4 kata kadar konutlarda hidrofor amaçlı pompa</li>
<li>Villa ve yazlık temiz su temini</li>
</ul>

<h3>Kurulum notu</h3>
<p>İlk çalışmada pompa gövdesine su doldurulmalıdır. Emiş hattı pompa seviyesinin altındaysa dip klapesi / çekvalf kullanın. Emiş hattına pislik tutucu önerilir. Susuz çalıştırmayın. Sıcak su hattı için uygun değildir.</p>
HTML;
    }
};
