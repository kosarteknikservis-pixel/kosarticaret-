<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-smj-220-jet-paket-hidrofor-8-kat-16-daire-50-litre-tankli';

    private const TRENDYOL_PRICE = 34199.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'SMJH22050L',
                'sku' => filled($product->sku) ? $product->sku : 'SMJH220',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 32.00,
                'width_cm' => $product->width_cm ?: 40,
                'height_cm' => $product->height_cm ?: 80,
                'depth_cm' => $product->depth_cm ?: 55,
                'short_description' => 'Sumak SMJ 220 Jet paket hidrofor: 2.2 HP (1.6 kW), 50 litre tank. 8 kat / 16 daire bina basınçlandırması.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Motor Gücü (HP)' => '2.2 HP',
                    'Motor Gücü (kW)' => '1.6 kW',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Maks. Debi (m³/h)' => '6-10 m3/h',
                    'Maks. Basma Yüksekliği (mSS)' => '51-75 mss',
                    'Kat Sayısı' => '8 Kat',
                    'Daire Sayısı' => '16 Daire',
                    'Giriş Ölçüsü' => '1.1/4" - 32 mm',
                    'Çıkış Ölçüsü' => '1" - 25 mm',
                    'Devir (d/d)' => '2900 d/d',
                    'Emiş Derinliği' => '9 Metre',
                    'Tank' => '50 Litre',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'sumak-hidrofor')->value('id')
                ?: Category::query()->where('slug', 'hidroforlar')->value('id');
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
                'external_sku' => $product->sku ?: 'SMJH220',
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
<h2>Sumak SMJ 220 Jet Paket Hidrofor 8 Kat 16 Daire 50 Litre Tanklı</h2>
<p>Sumak SMJ 220 Jet, 2.2 HP (1.6 kW) monofaze jet paket hidrofordur. 50 litre genleşme tankı ile 8 kat / 16 daireye kadar yapılarda su basıncını yükseltmek için kullanılır. Su talebi başladığında otomatik devreye girer; talep bitince basınç şalteri pompayı durdurur.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SMJ 220 Jet</td></tr>
<tr><td>Motor gücü</td><td>2.2 HP (1.6 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. debi</td><td>6–10 m³/h</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>51–75 mss</td></tr>
<tr><td>Kapasite</td><td>8 kat / 16 daire</td></tr>
<tr><td>Tank</td><td>50 litre</td></tr>
<tr><td>Giriş / çıkış</td><td>1 1/4" – 1"</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Emiş derinliği</td><td>9 metre</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>8 kata kadar apartman basınçlandırması</li>
<li>Villa ve müstakil evler</li>
<li>İş yeri ve atölye su basıncı desteği</li>
<li>Depodan emişli jet hidrofor uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>Emiş hattı pompa emiş çapına uygun tutulmalı, daraltılmamalıdır. Emiş ucunda dip klapesi kullanın. İlk çalışmada pompa gövdesine su doldurulmalıdır. Depoda su bitmesine karşı seviye flatörü önerilir. Genleşme tankı ön gaz basıncı periyodik kontrol edilmelidir. Sıcak su hattı için uygun değildir.</p>
HTML;
    }
};
