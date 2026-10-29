<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'kaysu-hqbm60-preferikal-surtme-fanli-su-pompasi';

    private const TRENDYOL_PRICE = 3699.00;

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
                : 'HQBM60';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) && ! in_array((string) $product->barcode, ['51', '24', ''], true)
                    ? $product->barcode
                    : 'HQBM60',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 5.50,
                'width_cm' => $product->width_cm ?: 18,
                'height_cm' => $product->height_cm ?: 22,
                'depth_cm' => $product->depth_cm ?: 28,
                'short_description' => 'Kaysu HQBm60 preferikal sürtme fanlı yüzey pompası: 0.5 HP (0.37 kW), 220 V. 31–40 mSS, 0–5 m³/h. Çıkış 1", bronz çark.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Kaysu',
                    'Model' => 'HQBm60',
                    'Tip' => 'Preferikal (sürtme fanlı) yüzey tipi su pompası',
                    'Motor Gücü' => '0.50 HP (0.37 kW)',
                    'Voltaj' => '220 V Monofaze',
                    'Maks. Basma Yüksekliği' => '31–40 mSS',
                    'Debi Aralığı' => '0–5 m³/sa',
                    'Çıkış Ölçüsü' => '1" – 25 mm',
                    'Maks. Sıvı Sıcaklığı' => '60 °C',
                    'Çark Malzemesi' => 'Bronz',
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
                'external_sku' => $product->fresh()->sku ?: 'HQBM60',
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
<h2>Kaysu HQBm60 Preferikal Sürtme Fanlı Su Pompası</h2>
<p>Kaysu HQBm60, preferikal (sürtme fanlı) yüzey tipi su pompasıdır. 0.50 HP (0.37 kW), 220 V monofaze. Yüksek basınç–düşük debi profili ile noktasal basınçlandırmada kullanılır. Bronz çarklıdır; temiz/berrak su içindir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Kaysu HQBm60</td></tr>
<tr><td>Motor gücü</td><td>0.50 HP (0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. basma</td><td>31–40 mSS</td></tr>
<tr><td>Debi</td><td>0–5 m³/sa</td></tr>
<tr><td>Çıkış</td><td>1" (25 mm)</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>Maks. 60 °C</td></tr>
<tr><td>Çark</td><td>Bronz</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Ev/konut noktasal basınç takviyesi</li>
<li>Küçük ekipman minimum besleme basıncı</li>
<li>Depo/varil beslemeli düşük debili sulama</li>
<li>Temiz su transferi (maks. 60 °C)</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yüzey tipi pompadır; dalgıç değildir. Emiş hattı kısa ve geniş tutulmalı, priming yapılmalı, kaliteli çekvalf kullanılmalıdır. Kirli/partiküllü veya kimyasal sıvılarda kullanılmaz.</p>
HTML;
    }
};
