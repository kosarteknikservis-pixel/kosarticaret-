<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'kaysu-2hcp-160-cift-kademeli-hidrofor-7-kat-14-daire-50-litre-tankli';

    private const TRENDYOL_PRICE = 18199.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : '2HCP16050L',
                'sku' => filled($product->sku) ? $product->sku : '2HCP-160',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 28.00,
                'width_cm' => $product->width_cm ?: 40,
                'height_cm' => $product->height_cm ?: 75,
                'depth_cm' => $product->depth_cm ?: 55,
                'short_description' => 'Kaysu 2HCP-160 çift kademeli paket hidrofor: 2 HP (1.5 kW), 50 litre tank. 7 kat / 14 daire bina basınçlandırması.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka / Model' => 'Kaysu 2HCP-160',
                    'Kullanım Kapasitesi' => '7 Kat / 14 Daireye Kadar',
                    'Motor Gücü' => '2 HP (1.5 kW) Bakır Sargı',
                    'Voltaj' => '220V monofaze (380V seçenekli)',
                    'Tank Özelliği' => '50 Litre, değiştirilebilir membranlı',
                    'Maks. Basınç' => '60 mss (6 Bar)',
                    'Maks. Emiş' => '6 Metre',
                    'Giriş / Çıkış' => '1 1/4" emiş – 1" basma',
                    'Fan Tipi' => 'Çift fanlı (double impeller)',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'hidroforlar')->value('id');
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
                'external_sku' => $product->sku ?: '2HCP-160',
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
<h2>Kaysu 2HCP-160 Çift Kademeli Hidrofor 7 Kat 14 Daire 50 Litre Tanklı</h2>
<p>Kaysu 2HCP-160, 2 HP (1.5 kW) bakır sargılı motorlu çift kademeli (çift fanlı) paket hidrofordur. 50 litre değiştirilebilir membranlı genleşme tankı ile 7 kat / 14 daireye kadar yapılarda su basıncını yükseltmek için kullanılır. Su talebi başladığında otomatik devreye girer; talep bitince basınç şalteri pompayı durdurur.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka / Model</td><td>Kaysu 2HCP-160</td></tr>
<tr><td>Kapasite</td><td>7 kat / 14 daire</td></tr>
<tr><td>Motor gücü</td><td>2 HP (1.5 kW), bakır sargı</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze (380 V seçenekli)</td></tr>
<tr><td>Tank</td><td>50 litre, değiştirilebilir membranlı</td></tr>
<tr><td>Maks. basınç</td><td>60 mss (6 bar)</td></tr>
<tr><td>Maks. emiş</td><td>6 metre</td></tr>
<tr><td>Giriş / çıkış</td><td>1 1/4" emiş – 1" basma</td></tr>
<tr><td>Fan tipi</td><td>Çift fanlı (double impeller)</td></tr>
</tbody>
</table>

<h3>Paket içeriği</h3>
<ul>
<li>2 HP çift fanlı motor</li>
<li>50 litre genleşme tankı</li>
<li>Basınç şalteri</li>
<li>Manometre</li>
<li>5 yollu bağlantı</li>
<li>Flex hortum</li>
</ul>

<h3>Kullanım alanları</h3>
<ul>
<li>5–7 katlı apartman basınçlandırması</li>
<li>Tripleks ve villa tipi müstakil evler</li>
<li>Yurt ve pansiyon duş hatları</li>
<li>İş yeri ve atölye su basıncı desteği</li>
</ul>

<h3>Kurulum notu</h3>
<p>Emiş hattı pompa emiş çapına (1 1/4") uygun tutulmalı, daraltılmamalıdır. Emiş ucunda dip klapesi kullanın. İlk çalışmada pompa gövdesine su doldurulmalıdır. Depoda su bitmesine karşı seviye flatörü önerilir. Genleşme tankı ön gaz basıncı periyodik kontrol edilmelidir. Sıcak su hattı için uygun değildir.</p>
HTML;
    }
};
