<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'kaysu-sp100050-kapali-fanli-plastik-drenaj-dalgic-su-pompasi';

    private const TRENDYOL_PRICE = 6999.00;

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
                : 'SP1000-50';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) ? $product->barcode : 'SP100050',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 8.00,
                'width_cm' => $product->width_cm ?: 22,
                'height_cm' => $product->height_cm ?: 35,
                'depth_cm' => $product->depth_cm ?: 22,
                'short_description' => 'Kaysu SP1000/50 kapalı fanlı plastik drenaj dalgıç pompa: 1,3 HP (0,97 kW), 220 V. Maks. 11–15 m³/h, 11–20 mSS.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Kaysu',
                    'Model' => 'SP1000/50 (SP1000-50)',
                    'Motor Gücü' => '1,3 HP – 0,97 kW',
                    'Voltaj' => '220 V (Monofaze)',
                    'Maks. Basma Yüksekliği' => '11–20 mSS',
                    'Maks. Debi' => '11–15 m³/h',
                    'Maks. Daldırma Derinliği' => '5 m',
                    'Çıkış Ölçüsü' => '1" – 25 mm',
                    'Maks. Sıvı Sıcaklığı' => '35 °C',
                    'Gövde' => 'Plastik',
                    'Fan' => 'Kapalı fanlı',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'drenaj-dalgic-pompa')->value('id')
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
                'external_sku' => $product->fresh()->sku ?: 'SP1000-50',
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
<h2>Kaysu SP1000/50 Kapalı Fanlı Plastik Drenaj Dalgıç Su Pompası</h2>
<p>Kaysu SP1000/50, 1,3 HP (0,97 kW) monofaze kapalı fanlı plastik drenaj dalgıç pompasıdır. Bodrum, asansör çukuru, depo ve sarnıç gibi alanlarda temiz / az partiküllü su birikintilerinin tahliyesi için kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Kaysu SP1000/50</td></tr>
<tr><td>Motor gücü</td><td>1,3 HP (0,97 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. debi</td><td>11–15 m³/h</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>11–20 mSS</td></tr>
<tr><td>Maks. daldırma</td><td>5 m</td></tr>
<tr><td>Çıkış</td><td>1" (25 mm)</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>maks. 35 °C</td></tr>
<tr><td>Gövde / fan</td><td>Plastik / kapalı fan</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Bodrum ve otopark drenajı</li>
<li>Asansör çukuru tahliyesi</li>
<li>Depo ve sarnıç boşaltımı</li>
<li>Şantiye / yağmur birikinti tahliyesi</li>
</ul>

<h3>Kurulum notu</h3>
<p>Pompa giriş ızgarası su altında çalıştırılmalıdır; kuru çalışma motora zarar verir. Temiz / az partiküllü sular içindir; çamurlu veya iri parçalı akışkanlar için uygun değildir. Topraklama ve kaçak akım koruması önerilir. Çıkış hattı 1" (25 mm) çapta tutulmalıdır.</p>
HTML;
    }
};
