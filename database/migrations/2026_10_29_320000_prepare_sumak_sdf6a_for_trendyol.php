<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sdf-6a-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp';

    private const TRENDYOL_PRICE = 15199.00;

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
            $sku = filled($product->sku) && ! in_array((string) $product->sku, ['51', ''], true)
                ? $product->sku
                : 'SDF6A';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) ? $product->barcode : 'SDF6A',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 6.50,
                'width_cm' => $product->width_cm ?: 22,
                'height_cm' => $product->height_cm ?: 35,
                'depth_cm' => $product->depth_cm ?: 20,
                'short_description' => 'Sumak SDF 6A paslanmaz gövdeli flatörlü drenaj dalgıç pompa: 0.5 HP (0.37 kW), 220 V. Maks. 7 mss, 0–5 m³/h. Çıkış 1¼".',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SDF 6A',
                    'Tip' => 'Paslanmaz gövdeli flatörlü drenaj dalgıç pompa',
                    'Motor Gücü' => '0.5 HP (0.37 kW)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Maks. Debi' => '0–5 m³/h',
                    'Maks. Basma Yüksekliği' => '7 m',
                    'Çıkış Ölçüsü' => '1.1/4" (32 mm)',
                    'Devir' => '2900 d/d',
                    'Gövde' => 'Paslanmaz çelik',
                    'Flatör' => 'Dahili / otomatik',
                    'Kablo' => '10 m',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'paslanmaz-drenaj-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'drenaj-dalgic-pompa')->value('id')
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
                'external_sku' => $product->fresh()->sku ?: 'SDF6A',
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
<h2>Sumak SDF 6A Drenaj Dalgıç Pompa Paslanmaz Gövdeli Monofaze (220 V) 0.5 Hp</h2>
<p>Sumak SDF 6A, paslanmaz çelik gövdeli, monofaze 220 V flatörlü drenaj dalgıç pompasıdır. Maksimum 7 m basma ve yaklaşık 70 m yatay tahliye kapasitesi ile inşaat, bodrum, yağmur suyu ve birikinti tahliyesinde kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SDF 6A</td></tr>
<tr><td>Motor gücü</td><td>0.5 HP (0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h</td></tr>
<tr><td>Maks. basma</td><td>7 m</td></tr>
<tr><td>Çıkış</td><td>1.1/4" (32 mm)</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Gövde</td><td>Paslanmaz çelik</td></tr>
<tr><td>Kablo</td><td>10 m</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Bodrum ve inşaat alanı su tahliyesi</li>
<li>Yağmur / birikinti suyu boşaltımı</li>
<li>Evsel ve endüstriyel atık su drenajı</li>
<li>Süs havuzu seviye kontrolü</li>
</ul>

<h3>Kurulum notu</h3>
<p>Flatörlü otomatik şamandıra, su seviyesi yükselince pompayı devreye alır ve tahliye bitince durdurur. Yalnızca 220 V monofaze besleme ile çalışır. Pompayı kuru çalıştırmayın.</p>
HTML;
    }
};
