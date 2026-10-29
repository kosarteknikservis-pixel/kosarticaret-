<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'pedrollo-bc-1550n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa';

    private const TRENDYOL_PRICE = 41799.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'BC1550N',
                'sku' => filled($product->sku) ? $product->sku : 'BC1550N',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 22.00,
                'width_cm' => $product->width_cm ?: 30,
                'height_cm' => $product->height_cm ?: 55,
                'depth_cm' => $product->depth_cm ?: 30,
                'short_description' => 'Pedrollo BC 15/50N flatörlü paslanmaz foseptik dalgıç pompa: 1.1 kW (1.5 HP), 380 V. Çıkış 2", maks. 26–50 m³/h.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Motor Gücü' => '1.1 kW (1.5 HP)',
                    'Voltaj' => '380 V',
                    'Çıkış Ölçüsü' => '2" (50 mm)',
                    'Maks. Debi' => '26-50 m³/h',
                    'Maks. Basma Yüksekliği' => '11-20 m',
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
                'external_sku' => $product->sku ?: 'BC1550N',
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
<h2>Pedrollo BC 15/50N Flatörlü Paslanmaz Gövdeli Foseptik Dalgıç Pompa</h2>
<p>Pedrollo BC 15/50N, 1.1 kW (1.5 HP) trifaze (380 V) paslanmaz gövdeli foseptik / kirli su dalgıç pompasıdır. Flatörlü (şamandıralı) yapı sayesinde su seviyesine göre otomatik çalışır. Foseptik, drenaj ve inşaat alanı tahliyelerinde kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Pedrollo BC 15/50N</td></tr>
<tr><td>Motor gücü</td><td>1.1 kW (1.5 HP)</td></tr>
<tr><td>Voltaj</td><td>380 V trifaze</td></tr>
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
<li>Endüstriyel pis su uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>380 V trifaze besleme gerekir; monofaze hat için uygun değildir. Flatör serbest hareket edebilecek şekilde bağlanmalıdır. Pompa asılı veya taban destekli monte edilmeli, emiş ağzı tıkanmaya karşı korunmalıdır. Kablo ve bağlantılar suya uygun şekilde yalıtılmalıdır.</p>
HTML;
    }
};
