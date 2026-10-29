<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const SLUG = 'pedrollo-top-multi-ad-drenaj-dalgic-pompa-mazot-pompasi-27-mss-4-2-m3h';

    private const PRICE = 23199.00;

    private const TRENDYOL_CATEGORY_ID = '2600';

    private const TRENDYOL_CATEGORY_NAME = 'Su Motoru ve Pompa';

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'pedrollo')->first();
        if ($brand === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Pedrollo markası yok.');
        }

        $categorySlugs = ['drenaj-dalgic-pompa', 'dalgic-pompalar', 'su-pompalari'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'drenaj-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'dalgic-pompalar')->value('id');
            if (! $fallback) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('drenaj-dalgic-pompa kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Pedrollo TOP MULTI-AD Drenaj Dalgıç Pompa Mazot Pompası 27 mss 4.2 m³/h';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'TOPMULTI-AD',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'TOPMULTIAD',
                'name' => $name,
                'short_description' => 'Pedrollo TOP MULTI-AD çok kademeli drenaj dalgıç pompa: 0.50 HP (0.37 kW), 220 V. Maks. ~27 mss, 4.2 m³/h; mazot aktarımına da uygun.',
                'description' => $this->descriptionHtml(),
                'brand_id' => $brand->id,
                'price' => self::PRICE,
                'compare_at_price' => null,
                'stock' => max(1, (int) ($product?->stock ?? 1)),
                'featured' => false,
                'is_active' => true,
                'marketplace_enabled' => true,
                'image' => $paths[0],
                'image_alt' => $name,
                'meta_title' => $name,
                'meta_description' => 'Pedrollo TOP MULTI-AD: 0.50 HP drenaj dalgıç / mazot pompası. 220 V, 1 1/4" çıkış, maks. 27 mss. Teknik tablo ve kullanım notu.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 8.00,
                'width_cm' => $product?->width_cm ?: 20,
                'height_cm' => $product?->height_cm ?: 35,
                'depth_cm' => $product?->depth_cm ?: 20,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Pedrollo',
                    'Model' => 'TOP MULTI-AD',
                    'Tip' => 'Çok kademeli drenaj dalgıç / mazot pompası',
                    'Motor Gücü (HP)' => '0.50 HP',
                    'Motor Gücü (kW)' => '0.37 kW',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Maks. Debi' => '0–5 m³/h (4.2 m³/h)',
                    'Maks. Basma Yüksekliği' => '21–30 mss (~27 mss)',
                    'Çıkış Ölçüsü' => '1 1/4" - 32 mm',
                    'Çalışma derinliği' => '3 metre',
                    'Sıvı sıcaklığı' => 'maks. 40 °C',
                    'Kablo' => '5 m fişli',
                ],
            ];

            if ($product === null) {
                $product = Product::query()->create($payload);
            } else {
                $product->fill($payload)->save();
                $product->images()->delete();
            }

            $product->categories()->sync($categoryIds->all());

            foreach ($paths as $index => $path) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'alt' => $name.($index === 0 ? '' : ' — görünüm '.($index + 1)),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        $product = Product::query()->where('slug', self::SLUG)->firstOrFail();

        foreach ($paths as $path) {
            ImageVariant::generate($path, array_values(array_unique([
                ...ImageVariant::presetsFor('product'),
                ...ImageVariant::presetsFor('product-gallery'),
            ])));
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
                'external_sku' => 'TOPMULTI-AD',
                'channel_price' => self::PRICE,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        // Katalog kaydı silinmez.
    }

    /**
     * @return list<string>
     */
    private function publishImages(): array
    {
        $source = database_path('seed-assets/pedrollo/top-multi-ad-1.jpg');
        if (! is_file($source)) {
            throw new \RuntimeException('Pedrollo TOP MULTI-AD görseli yok: '.$source);
        }

        $target = 'products/pedrollo/top-multi-ad-1.jpg';
        Storage::disk('public')->put($target, (string) file_get_contents($source));

        return [$target];
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Pedrollo TOP MULTI-AD Drenaj Dalgıç Pompa Mazot Pompası</h2>
<p>Pedrollo TOP MULTI-AD, 0.50 HP (0.37 kW) monofaze çok kademeli drenaj dalgıç pompasıdır. Temiz ve az kirli suyun boşaltılmasında; garaj, depo ve havuz birikintilerinde kullanılır. Dizel yakıt (mazot) aktarımında da kullanılabilir. Motor soğuması için pompa tamamen sıvı altında çalışmalıdır. 5 metre fişli kablo dahildir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Pedrollo TOP MULTI-AD</td></tr>
<tr><td>Motor gücü</td><td>0.50 HP (0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze (230 V – 50 Hz)</td></tr>
<tr><td>Çıkış</td><td>1 1/4" (32 mm)</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h (4.2 m³/h)</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>21–30 mss (~27 mss)</td></tr>
<tr><td>Çalışma derinliği</td><td>3 metre</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>maks. 40 °C</td></tr>
<tr><td>Kablo</td><td>5 m fişli</td></tr>
</tbody>
</table>

<h3>Debi / basma örneği</h3>
<table>
<thead>
<tr><th>Debi</th><th>Dikey basma</th></tr>
</thead>
<tbody>
<tr><td>0.6 m³/h</td><td>25 m</td></tr>
<tr><td>1.2 m³/h</td><td>22.5 m</td></tr>
<tr><td>1.8 m³/h</td><td>19.5 m</td></tr>
<tr><td>2.4 m³/h</td><td>16.5 m</td></tr>
<tr><td>3.0 m³/h</td><td>13 m</td></tr>
<tr><td>3.6 m³/h</td><td>9 m</td></tr>
<tr><td>4.2 m³/h</td><td>5 m</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Garaj, depo ve havuz birikinti tahliyesi</li>
<li>Temiz / az kirli su drenajı</li>
<li>Ev tipi kuyu su tedariki (uygun koşullarda)</li>
<li>Dizel yakıt (mazot) aktarımı</li>
</ul>

<h3>Kurulum notu</h3>
<p>Pompa tamamen sıvı altında çalıştırılmalıdır. Çalışma derinliği 3 metreyi aşmamalıdır. Partikül boyutu sınırlıdır; iri tortulu veya foseptik su için uygun değildir. Yakıt aktarımında yalnızca üreticinin izin verdiği sıvılar kullanılmalı, elektrik güvenliği kurallarına uyulmalıdır.</p>

<p>Benzer modeller için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompalar</a> kategorisine bakın. Marka özeti: <a href="/marka/pedrollo">Pedrollo</a>.</p>
HTML;
    }
};
