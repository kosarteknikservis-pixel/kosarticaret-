<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'pedrollo-bcm-1050n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa';

    private const TRENDYOL_PRICE = 35999.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'BCM1050N',
                'sku' => filled($product->sku) ? $product->sku : 'BCM1050N',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 18.00,
                'width_cm' => $product->width_cm ?: 28,
                'height_cm' => $product->height_cm ?: 50,
                'depth_cm' => $product->depth_cm ?: 28,
                'short_description' => 'Pedrollo BCm 10/50N flatörlü paslanmaz foseptik dalgıç pompa: 0.75 kW (1 HP), 220 V monofaze. Çıkış 2", maks. 26–50 m³/h.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Motor Gücü' => '0.75 kW (1 HP)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Maks. Debi' => '26-50 m³/h',
                    'Maks. Basma Yüksekliği' => '11-20 m',
                    'Çıkış Ölçüsü' => '2" (50 mm)',
                    'Flatör' => 'Var (otomatik seviye)',
                    'Gövde' => 'Paslanmaz',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'foseptik-dalgic-pompa')->value('id')
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
                'external_sku' => $product->sku ?: 'BCM1050N',
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
<h2>Pedrollo BCm 10/50N Flatörlü Paslanmaz Gövdeli Foseptik Dalgıç Pompa</h2>
<p>Pedrollo BCm 10/50N, 0.75 kW (1 HP) monofaze (220 V) paslanmaz gövdeli foseptik / kirli su dalgıç pompasıdır. Flatörlü (şamandıralı) yapı sayesinde su seviyesine göre otomatik çalışır. Foseptik, drenaj ve inşaat alanı tahliyelerinde kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Pedrollo BCm 10/50N</td></tr>
<tr><td>Motor gücü</td><td>0.75 kW (1 HP)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Çıkış</td><td>2" (50 mm)</td></tr>
<tr><td>Maks. debi</td><td>26–50 m³/h</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>11–20 m</td></tr>
<tr><td>Gövde</td><td>Paslanmaz</td></tr>
<tr><td>Flatör</td><td>Var</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Foseptik ve pis su tahliyesi</li>
<li>Kirli su drenajı</li>
<li>Sel ve inşaat sahası tahliyesi</li>
<li>Monofaze hatlı ev / işyeri uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>220 V monofaze besleme ile çalışır. Flatör serbest hareket edebilecek şekilde bağlanmalıdır. Pompa asılı veya taban destekli monte edilmeli, emiş ağzı tıkanmaya karşı korunmalıdır. Kablo ve bağlantılar suya uygun şekilde yalıtılmalıdır.</p>
HTML;
    }
};
