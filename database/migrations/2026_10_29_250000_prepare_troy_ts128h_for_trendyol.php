<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'troy-ts-12-8-h-12v-karavan-hidroforu';

    private const TRENDYOL_PRICE = 7999.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'TS128H',
                'sku' => filled($product->sku) ? $product->sku : 'TS12-8H',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 2.60,
                'width_cm' => $product->width_cm ?: 13,
                'height_cm' => $product->height_cm ?: 11,
                'depth_cm' => $product->depth_cm ?: 21,
                'short_description' => 'Troy TS 12-8 H 12 V DC karavan/tekne hidroforu: 72 W, 6 A, 450 L/h, 45 m basma. Ağırlık 2,6 kg.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Model' => 'TS 12-8 H',
                    'Voltaj' => '12 V DC',
                    'Motor Gücü' => '72 W',
                    'Akım' => '6 A',
                    'Maks. Debi' => '450 L/h (7,5 lt/dk)',
                    'Maks. Basma Yüksekliği' => '45 m',
                    'Maks. Sıvı Sıcaklığı' => '60 °C',
                    'Ağırlık' => '2,6 kg',
                    'Kablo Uzunluğu' => '1 m',
                    'Emiş Derinliği' => '2 m',
                    'Giriş / Çıkış Ölçüsü' => '1/2 inç',
                    'Ölçüler (G × U × Y)' => '128 × 212 × 110 mm',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'karavan-hidroforu')->value('id')
                ?: Category::query()->where('slug', 'hidroforlar')->value('id');
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
                'external_sku' => $product->sku ?: 'TS12-8H',
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
<h2>Troy TS 12-8 H 12 V Karavan Hidroforu</h2>
<p>Troy TS 12-8 H, 12 V DC karavan ve tekne hidroforudur. Etiket değerleri: 72 W, 6 A, H max 45 m, Q max 450 L/h (7,5 lt/dk), ağırlık 2,6 kg. Azami sıvı sıcaklığı 60 °C. Yanıcı sıvı ve patlayıcı ortamda kullanılmamalıdır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>TS 12-8 H</td></tr>
<tr><td>Voltaj</td><td>12 V DC</td></tr>
<tr><td>Motor gücü</td><td>72 W</td></tr>
<tr><td>Akım</td><td>6 A</td></tr>
<tr><td>Maks. debi</td><td>450 L/h</td></tr>
<tr><td>Maks. basma</td><td>45 m</td></tr>
<tr><td>Emiş</td><td>2 m</td></tr>
<tr><td>Giriş / çıkış</td><td>1/2 inç</td></tr>
<tr><td>Kablo</td><td>1 m</td></tr>
<tr><td>Ölçü / ağırlık</td><td>128 × 212 × 110 mm / 2,6 kg</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>maks. 60 °C</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Karavan temiz su basınçlandırması</li>
<li>Tekne / yat 12 V su hattı</li>
<li>Kamp / mobil 12 V DC su pompası</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 12 V DC besleme ile çalışır; 220 V şebekeye bağlanmaz. Akü kutuplarına doğru polaritede bağlayın. Temiz su içindir; yanıcı sıvı basmayın. İlk çalışmada pompa gövdesine su doldurulmalıdır.</p>
HTML;
    }
};
