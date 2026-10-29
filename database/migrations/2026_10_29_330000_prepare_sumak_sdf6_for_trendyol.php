<?php

use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SLUG = 'sumak-sdf-6-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp';

    private const TRENDYOL_PRICE = 11899.00;

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
                : 'SDF6';

            $product->fill([
                'marketplace_enabled' => true,
                'is_active' => true,
                'barcode' => filled($product->barcode) ? $product->barcode : 'SDF6',
                'sku' => $sku,
                'weight_kg' => $product->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 6.00,
                'width_cm' => $product->width_cm ?: 22,
                'height_cm' => $product->height_cm ?: 35,
                'depth_cm' => $product->depth_cm ?: 20,
                'short_description' => 'Sumak SDF 6 paslanmaz gövdeli drenaj dalgıç pompa: 0.5 HP, 220 V. Maks. 0–5 m³/h, 1–10 mSS. Çıkış 1¼", 10 m kablo.',
                'description' => $this->descriptionHtml(),
                'specs' => array_merge(is_array($product->specs) ? $product->specs : [], [
                    'Marka' => 'Sumak',
                    'Model' => 'SDF 6',
                    'Tip' => 'Paslanmaz gövdeli drenaj dalgıç pompa',
                    'Motor Gücü' => '0.5 HP',
                    'Voltaj' => '220 V (Monofaze)',
                    'Giriş / Çıkış Ölçüsü' => '1.1/4" (32 mm)',
                    'Maks. Debi' => '0–5 m³/h',
                    'Maks. Basma Yüksekliği' => '1–10 mSS',
                    'Devir' => '2900 d/d',
                    'Gövde' => 'Paslanmaz çelik',
                    'Partikül' => 'Maks. 3 mm',
                    'Kablo' => '10 m',
                ]),
            ])->save();
        });

        $categoryIds = $product->fresh()->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'paslanmaz-drenaj-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'drenaj-dalgic-pompa')->value('id')
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
                'external_sku' => $product->fresh()->sku ?: 'SDF6',
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
<h2>Sumak SDF 6 Drenaj Dalgıç Pompa Paslanmaz Gövdeli Monofaze (220 V) 0.5 Hp</h2>
<p>Sumak SDF 6, paslanmaz gövdeli monofaze drenaj dalgıç pompasıdır. 0.5 HP ile yaklaşık 7 m dikey / 70 m yatay tahliye kapasitesine sahiptir. Maks. 3 mm partikül geçirgenliği ile kuyu, asansör çukuru, bodrum ve yağmur suyu tahliyesinde kullanılır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SDF 6</td></tr>
<tr><td>Motor gücü</td><td>0.5 HP</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Giriş / çıkış</td><td>1.1/4" (32 mm)</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h</td></tr>
<tr><td>Maks. basma</td><td>1–10 mSS</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Gövde</td><td>Paslanmaz çelik</td></tr>
<tr><td>Kablo</td><td>10 m</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Kuyu ve asansör çukuru tahliyesi</li>
<li>Bodrum / kazan dairesi birikintileri</li>
<li>Yağmur suyu ve şelale uygulamaları</li>
<li>Göl / dere kaynaklı birikinti boşaltımı</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Pompayı kuru çalıştırmayın. Küçük partiküllü (maks. ~3 mm) temiz–az kirli sular içindir.</p>
HTML;
    }
};
