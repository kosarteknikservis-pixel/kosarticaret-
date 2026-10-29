<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sbrm-182p-parcalayici-bicakli-foseptik-dalgic-pompa-monofaze-220v-18-hp';

    private const TRENDYOL_PRICE = 33399.00;

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
                : 'SBRM18-2-P';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) && ! in_array((string) $product->barcode, ['51', '24', ''], true)
                    ? $product->barcode
                    : 'SBRM18-2-P',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 18.00,
                'width_cm' => $product->width_cm ?: 25,
                'height_cm' => $product->height_cm ?: 45,
                'depth_cm' => $product->depth_cm ?: 25,
                'short_description' => 'Sumak SBRM 18/2P parçalayıcı bıçaklı foseptik dalgıç pompa: 1.8 HP (1.35 kW), 220 V, 2" çıkış. Maks. 11 mss dikey / 110 m yatay. Paslanmaz gövde.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SBRM 18/2P',
                    'Tip' => 'Parçalayıcı bıçaklı foseptik dalgıç pompa',
                    'Motor Gücü' => '1.8 HP (1.35 kW)',
                    'Voltaj' => '220 V (Monofaze)',
                    'Akım' => '6.3 A',
                    'Giriş / Çıkış Ölçüsü' => '2" (50 mm)',
                    'Devir' => '2900 d/d',
                    'Maks. Basma (dikey)' => '11 m',
                    'Maks. Basma (yatay)' => '110 m',
                    'Maks. Sıvı Sıcaklığı' => '30 °C',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'bicakli-dalgic-pompa')->value('id')
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
                'external_sku' => $product->fresh()->sku ?: 'SBRM18-2-P',
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
<h2>Sumak SBRM 18/2P Parçalayıcı Bıçaklı Foseptik Dalgıç Pompa Monofaze (220V) 1.8 Hp</h2>
<p>Sumak SBRM 18/2P, 1.8 HP (1.35 kW) monofaze parçalayıcı bıçaklı foseptik dalgıç pompadır. Paslanmaz gövdesi ile zorlu atık su uygulamalarında kullanılır. Bıçaklı yapı, lifli ve katı partikülleri parçalayarak tahliyeyi kolaylaştırır. Maksimum yaklaşık 11 m dikey ve 110 m yatay basma kapasitesine sahiptir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SBRM 18/2P</td></tr>
<tr><td>Motor gücü</td><td>1.8 HP (1.35 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Akım</td><td>6.3 A</td></tr>
<tr><td>Giriş / çıkış</td><td>2" (50 mm)</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Maks. basma (dikey)</td><td>11 m</td></tr>
<tr><td>Maks. basma (yatay)</td><td>110 m</td></tr>
<tr><td>Maks. sıvı sıcaklığı</td><td>30 °C</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Ev ve bina atık su tahliyesi</li>
<li>Kanalizasyon ve foseptik sistemleri</li>
<li>Yağmur suyu tahliyesi</li>
<li>İnşaat ve endüstriyel atık su yönetimi</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Pompayı kuru çalıştırmayın. Bıçaklı modellerde çıkış hattı tıkanmaya karşı uygun çap ve eğimde olmalıdır.</p>
HTML;
    }
};
