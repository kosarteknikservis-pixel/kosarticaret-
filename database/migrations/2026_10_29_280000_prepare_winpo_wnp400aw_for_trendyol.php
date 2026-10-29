<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v';

    private const TRENDYOL_PRICE = 5599.00;

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
                : 'WNP400AW';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) ? $product->barcode : 'WNP400AW',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 6.00,
                'width_cm' => $product->width_cm ?: 22,
                'height_cm' => $product->height_cm ?: 35,
                'depth_cm' => $product->depth_cm ?: 20,
                'short_description' => 'Winpo WNP 400 AW flatörlü drenaj dalgıç pompa: 0.5 HP, 220 V. Debi 6–8 m³/h, maks. 7 mss. Çıkış 1¼", 10 m kablo.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Winpo',
                    'Model' => 'WNP 400 AW',
                    'Tip' => 'Flatörlü drenaj dalgıç pompa',
                    'Motor Gücü' => '0.5 HP',
                    'Voltaj' => '220 V - 50 Hz',
                    'Maks. Debi' => '6–8 m³/h',
                    'Maks. Basma Yüksekliği' => '7 mss',
                    'Maks. Çalışma Derinliği' => '5 m',
                    'Çıkış Bağlantı Çapı' => '1.1/4"',
                    'Maks. Partikül Çapı' => '35 mm',
                    'Kablo Uzunluğu' => '10 m',
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
                'external_sku' => $product->fresh()->sku ?: 'WNP400AW',
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
<h2>Winpo WNP 400 AW Flatörlü Drenaj Dalgıç Pompa 6 mss 7 m³/h Monofaze (220V)</h2>
<p>Winpo WNP 400 AW, 0.5 HP monofaze flatörlü drenaj dalgıç pompasıdır. Bodrum, asansör boşluğu ve birikinti su tahliyesinde kullanılır. Maksimum debi 6–8 m³/h, maksimum basma yüksekliği 7 mss’tir. Çıkış 1¼", kablo 10 m, flatör dahildir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Winpo WNP 400 AW</td></tr>
<tr><td>Motor gücü</td><td>0.5 HP</td></tr>
<tr><td>Voltaj</td><td>220 V - 50 Hz</td></tr>
<tr><td>Maks. debi</td><td>6–8 m³/h</td></tr>
<tr><td>Maks. basma</td><td>7 mss</td></tr>
<tr><td>Çalışma derinliği</td><td>Maks. 5 m</td></tr>
<tr><td>Çıkış</td><td>1.1/4"</td></tr>
<tr><td>Partikül</td><td>Maks. 35 mm</td></tr>
<tr><td>Kablo</td><td>10 m (flatörlü)</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Bodrum ve otopark drenajı</li>
<li>Asansör boşluğu tahliyesi</li>
<li>Birikinti / yağmur suyu boşaltımı</li>
<li>Temiz–az partiküllü drenaj uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Flatör serbest hareket edecek şekilde konumlandırın; pompayı kuru çalıştırmayın. Maksimum çalışma derinliği 5 m’dir.</p>
HTML;
    }
};
