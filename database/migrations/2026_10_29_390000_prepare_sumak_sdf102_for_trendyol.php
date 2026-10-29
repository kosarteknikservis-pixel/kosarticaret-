<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sdf-102-paslanmaz-govdeli-foseptik-dalgic-pompa-monofaze-220-v-1-hp';

    private const TRENDYOL_PRICE = 18970.00;

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
                : 'SDF102';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) && ! in_array((string) $product->barcode, ['51', '24', ''], true)
                    ? $product->barcode
                    : 'SDF102',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 12.00,
                'width_cm' => $product->width_cm ?: 22,
                'height_cm' => $product->height_cm ?: 40,
                'depth_cm' => $product->depth_cm ?: 22,
                'short_description' => 'Sumak SDF 10/2 paslanmaz gövdeli foseptik dalgıç pompa: 1 HP (0.75 kW), 220 V, 2" çıkış. Maks. 10.5 m dikey / 105 m yatay. Flatörlü.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SDF 10/2',
                    'Tip' => 'Paslanmaz gövdeli foseptik / drenaj dalgıç pompa',
                    'Motor Gücü' => '1 HP (0.75 kW)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Çıkış Ölçüsü' => '2" (50 mm)',
                    'Devir' => '2900 d/d',
                    'Maks. Basma (dikey)' => '10.5 m',
                    'Maks. Basma (yatay)' => '105 m',
                    'Maks. Sıvı Sıcaklığı' => '30 °C',
                    'Kablo' => '10 m (flatörlü)',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'paslanmaz-drenaj-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'foseptik-dalgic-pompa')->value('id')
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
                'external_sku' => $product->fresh()->sku ?: 'SDF102',
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
<h2>Sumak SDF 10/2 Paslanmaz Gövdeli Foseptik Dalgıç Pompa Monofaze (220 V) 1 Hp</h2>
<p>Sumak SDF 10/2, paslanmaz gövdeli, 1 HP (0.75 kW) monofaze foseptik / drenaj dalgıç pompasıdır. Az kirli su tahliyesinde kullanılır. Maksimum yaklaşık 10.5 m dikey ve 105 m yatay basma kapasitesine sahiptir. Flatör ile otomatik çalışır; 10 m kablo ile gelir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SDF 10/2</td></tr>
<tr><td>Motor gücü</td><td>1 HP (0.75 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Çıkış</td><td>2" (50 mm)</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Maks. basma (dikey)</td><td>10.5 m</td></tr>
<tr><td>Maks. basma (yatay)</td><td>105 m</td></tr>
<tr><td>Maks. sıvı sıcaklığı</td><td>30 °C</td></tr>
<tr><td>Kablo</td><td>10 m, flatörlü</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Kanalizasyon ve evsel atık su tahliyesi</li>
<li>Endüstriyel atık su sistemleri</li>
<li>Yağmur suyu drenajı</li>
<li>Küçük şelale / süs havuzu uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Flatör serbest hareket edecek şekilde konumlandırın; pompayı kuru çalıştırmayın.</p>
HTML;
    }
};
