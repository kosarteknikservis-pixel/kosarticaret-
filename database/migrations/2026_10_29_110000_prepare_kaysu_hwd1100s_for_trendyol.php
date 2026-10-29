<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'kaysu-hwd-1100s-paslanmaz-govdeli-kirli-su-dalgic-pompasi-15-hp-220-volt';

    private const TRENDYOL_PRICE = 14799.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'HWD1100S',
                'sku' => filled($product->sku) ? $product->sku : 'HWD-1100S',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 13.00,
                'width_cm' => $product->width_cm ?: 27.5,
                'height_cm' => $product->height_cm ?: 52.0,
                'depth_cm' => $product->depth_cm ?: 27.5,
                'short_description' => 'Kaysu HWD-1100S paslanmaz gövdeli kirli su dalgıç pompa: 1.5 HP (1.1 kW), 220 V, 2" çıkış, Ø40 mm katı geçişi, 0–24 m³/sa.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka / Model' => 'Kaysu HWD-1100S',
                    'Tip' => 'Paslanmaz gövdeli kirli su (drenaj) dalgıç pompa',
                    'Motor Gücü' => '1.5 HP (1.1 kW)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Çıkış Ölçüsü' => '2" – 50 mm',
                    'Maks. Katı Geçişi' => 'Ø 40 mm',
                    'Ağırlık (N.W)' => '13 kg',
                    'Boyutlar (L×W×H)' => '275 × 275 × 520 mm',
                    'Çalışma Aralığı' => '0–24 m³/sa',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'kirli-su-dalgic-pompa')->value('id')
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
                'external_sku' => $product->sku ?: 'HWD-1100S',
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
<h2>Kaysu HWD-1100S Paslanmaz Gövdeli Kirli Su Dalgıç Pompası 1.5 HP</h2>
<p>Kaysu HWD-1100S, çamurlu ve partiküllü suların tahliyesi için paslanmaz gövdeli kirli su (drenaj) dalgıç pompasıdır. 1.5 HP (1.1 kW) monofaze 220 V motor, 2" (50 mm) çıkış ve Ø40 mm’ye kadar katı geçişi ile yağmur suyu, taşkın, şantiye ve atölye drenajında kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka / Model</td><td>Kaysu HWD-1100S</td></tr>
<tr><td>Tip</td><td>Paslanmaz gövdeli kirli su dalgıç pompa</td></tr>
<tr><td>Motor gücü</td><td>1.5 HP (1.1 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Çıkış ölçüsü</td><td>2" (50 mm)</td></tr>
<tr><td>Maks. katı geçişi</td><td>Ø 40 mm</td></tr>
<tr><td>Ağırlık</td><td>13 kg</td></tr>
<tr><td>Boyutlar (L×W×H)</td><td>275 × 275 × 520 mm</td></tr>
<tr><td>Çalışma aralığı</td><td>0–24 m³/sa</td></tr>
</tbody>
</table>

<h3>Performans (Q–H)</h3>
<table>
<thead>
<tr><th>Q (m³/sa)</th><th>H (m)</th></tr>
</thead>
<tbody>
<tr><td>0</td><td>12.5</td></tr>
<tr><td>3</td><td>11.5</td></tr>
<tr><td>6</td><td>10.5</td></tr>
<tr><td>9</td><td>9.5</td></tr>
<tr><td>12</td><td>8.5</td></tr>
<tr><td>15</td><td>7</td></tr>
<tr><td>18</td><td>5.5</td></tr>
<tr><td>21</td><td>4</td></tr>
<tr><td>24</td><td>2.5</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Yağmur suyu, taşkın ve bodrum tahliyesi</li>
<li>Şantiye ve atölye drenajı</li>
<li>Atık su ara depolama kuyuları</li>
<li>Endüstriyel proses drenajı</li>
</ul>

<h3>Kurulum notu</h3>
<p>Pompayı kısa bir sehpa veya ızgara üzerinde konumlandırın. Çıkış hattına çekvalf ve kapama vanası ekleyin. Uygun kesitli kablo ve termik–manyetik koruma kullanın. Temiz su basınçlandırma veya foseptik öğütme için tasarlanmamıştır; partiküllü/çamurlu drenaj uygulamaları içindir.</p>
HTML;
    }
};
