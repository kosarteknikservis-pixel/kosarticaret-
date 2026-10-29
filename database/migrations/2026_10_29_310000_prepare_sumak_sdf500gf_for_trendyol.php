<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sdf500-gf-sifirdan-emisli-gizli-samandirali-drenaj-pompasi-05-hp';

    private const TRENDYOL_PRICE = 12399.00;

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
                : 'SDF500GF';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) ? $product->barcode : 'SDF500GF',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 5.00,
                'width_cm' => $product->width_cm ?: 20,
                'height_cm' => $product->height_cm ?: 32,
                'depth_cm' => $product->depth_cm ?: 18,
                'short_description' => 'Sumak SDF500 GF sıfırdan emişli gizli şamandıralı drenaj pompası: 0.5 HP (0.37 kW), 220 V. Maks. 6 mss, 3.5 m³/h. Çıkış 1¼"/1".',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SDF500 GF',
                    'Tip' => 'Sıfırdan emişli gizli şamandıralı drenaj pompası',
                    'Motor Gücü' => '0.5 HP (0.37 kW)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Çıkış Ölçüsü' => '1.1/4" (32 mm) - 1" (25 mm)',
                    'Maks. Basma Yüksekliği' => '6 m',
                    'Maks. Debi' => '3.5 m³/h',
                    'Maks. Sıvı Sıcaklığı' => '30 °C',
                    'Flatör' => 'Gizli şamandıra (ayarlanabilir)',
                    'Emiş' => 'Sıfırdan emiş (yaklaşık 3 mm)',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'drenaj-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'temiz-su-dalgic-pompa')->value('id')
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
                'external_sku' => $product->fresh()->sku ?: 'SDF500GF',
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
<h2>Sumak SDF500 GF Sıfırdan Emişli Gizli Şamandıralı Drenaj Pompası 0.5 Hp</h2>
<p>Sumak SDF500 GF, 0.5 HP monofaze, sıfırdan emişli ve gizli flatörlü drenaj pompasıdır. Zemin seviyesinden yaklaşık 3 mm’ye kadar emiş yapabilir. Su deposu, kuyu, havuz, bodrum ve az kirli birikinti tahliyesinde kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SDF500 GF</td></tr>
<tr><td>Motor gücü</td><td>0.5 HP (0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Çıkış</td><td>1.1/4" (32 mm) / 1" (25 mm)</td></tr>
<tr><td>Maks. basma</td><td>6 m</td></tr>
<tr><td>Maks. debi</td><td>3.5 m³/h</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>Maks. 30 °C</td></tr>
<tr><td>Flatör</td><td>Gizli şamandıra (ayarlanabilir)</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Su deposu temizliği</li>
<li>Asansör kuyusu birikintileri</li>
<li>Su baskını / bodrum tahliyesi</li>
</ul>

<h3>Kurulum notu</h3>
<p>Gizli flatör su seviyesine göre otomatik çalışabilir. Sürekli çalışma modunda yaklaşık 3 mm’ye kadar emiş yapılır; bu modda otomatik durma yoktur, su bitince manuel kapatılmalıdır. Yalnızca 220 V monofaze besleme ile çalışır.</p>
HTML;
    }
};
