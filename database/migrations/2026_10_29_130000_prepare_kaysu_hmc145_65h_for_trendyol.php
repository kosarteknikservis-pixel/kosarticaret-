<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'kaysu-pompa-hmc145-65h-paket-hidrofor-8-kat-18-daire-100-litre-tankli';

    private const TRENDYOL_PRICE = 21999.00;

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
                'barcode' => filled($product->barcode) ? $product->barcode : 'HMC14565H',
                'sku' => filled($product->sku) ? $product->sku : 'HMC145-65H',
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 42.00,
                'width_cm' => $product->width_cm ?: 45,
                'height_cm' => $product->height_cm ?: 95,
                'depth_cm' => $product->depth_cm ?: 70,
                'short_description' => 'Kaysu HMC145-65H paket hidrofor: 2.5 HP (1.85 kW), 220 V, 100 litre tank. 8 kat / 18 daire bina basınçlandırması.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Model' => 'Kaysu HMC145-65H',
                    'Motor Gücü' => '1.85 kW (2.5 HP)',
                    'Voltaj' => '220V / 50 Hz',
                    'Maks. Debi' => '9.6 m³/h',
                    'Maks. Basma Yüksekliği' => '76 mSS',
                    'Kapasite' => '8 kat / 18 daire',
                    'Giriş / Çıkış' => '1" – 1"',
                    'Tank Hacmi' => '100 Litre',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'hidroforlar')->value('id')
                ?: Category::query()->where('slug', 'ev-tipi-hidroforlar')->value('id');
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
                'external_sku' => $product->sku ?: 'HMC145-65H',
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
<h2>Kaysu HMC145-65H Paket Hidrofor 8 Kat 18 Daire 100 Litre Tanklı</h2>
<p>Kaysu HMC145-65H, 2.5 HP (1.85 kW) monofaze paket hidrofordur. 100 litre genleşme tankı ile 8 kat / 18 daireye kadar binalarda su basıncını yükseltmek için kullanılır. Su talebi başladığında otomatik devreye girer; talep bitince basınç şalteri pompayı durdurur.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Kaysu HMC145-65H</td></tr>
<tr><td>Motor gücü</td><td>1.85 kW (2.5 HP)</td></tr>
<tr><td>Voltaj</td><td>220 V / 50 Hz monofaze</td></tr>
<tr><td>Maks. debi</td><td>9.6 m³/h</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>76 mSS</td></tr>
<tr><td>Kapasite</td><td>8 kat / 18 daire</td></tr>
<tr><td>Giriş / çıkış</td><td>1" – 1"</td></tr>
<tr><td>Tank hacmi</td><td>100 litre</td></tr>
</tbody>
</table>

<h3>Performans noktaları</h3>
<ul>
<li>4 m³/h debide: 68 mSS</li>
<li>6 m³/h debide: 60 mSS</li>
<li>8 m³/h debide: 47 mSS</li>
</ul>

<h3>Kullanım alanları</h3>
<ul>
<li>8 kat / 18 daireye kadar apartman basınçlandırması</li>
<li>Siteler, rezidanslar ve sosyal tesisler</li>
<li>Oteller, yurtlar ve iş merkezleri</li>
<li>Depodan temiz su basma</li>
</ul>

<h3>Kurulum notu</h3>
<p>Tesisat ve elektrik bağlantıları tamamlandıktan sonra ilk çalışmada pompa gövdesine su doldurulmalıdır. Emiş hattında çekvalf veya dip klapesi kullanın. Depoda su bitmesine karşı seviye flatörü önerilir. Genleşme tankı ön gaz basıncı periyodik kontrol edilmelidir. Sıcak su hattı için uygun değildir.</p>
HTML;
    }
};
