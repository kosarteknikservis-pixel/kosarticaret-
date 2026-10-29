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
    private const SLUG = 'sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt';

    private const PRICE = 3999.00;

    private const TRENDYOL_CATEGORY_ID = '2600';

    private const TRENDYOL_CATEGORY_NAME = 'Su Motoru ve Pompa';

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'sumak')->first();
        if ($brand === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Sumak markası yok.');
        }

        $categorySlugs = ['drenaj-dalgic-pompa', 'kirli-su-dalgic-pompa', 'dalgic-pompalar'];
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
        $name = 'Sumak HD 500 Drenaj Dalgıç Pompa Açık (Vortex) Çarklı Monofaze 220V 370 Watt';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'HD500',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'HD500',
                'name' => $name,
                'short_description' => 'Sumak HD 500 flatörlü drenaj dalgıç pompa: 0.5 HP (0.37 kW), 220 V, açık (vortex) çark. Maks. ~5 m³/h, ~5.5 mss. 10 m kablo.',
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
                'meta_description' => 'Sumak HD 500: 0.5 HP flatörlü drenaj dalgıç pompa, 220 V, açık çark. Bodrum, birikinti ve kirli su tahliyesi.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 4.00,
                'width_cm' => $product?->width_cm ?: 22,
                'height_cm' => $product?->height_cm ?: 34,
                'depth_cm' => $product?->depth_cm ?: 16,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'HD 500',
                    'Tip' => 'Flatörlü drenaj dalgıç pompa (açık / vortex çark)',
                    'Motor Gücü (HP)' => '0.50 HP',
                    'Motor Gücü (kW)' => '0.37 kW',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Akım' => '1.1 A',
                    'Devir' => '2900 d/d',
                    'Kondansatör' => '7 µF',
                    'Çıkış Ölçüsü' => '1" / 1¼" / 1½" (universal)',
                    'Maks. Debi' => '0–5 m³/h',
                    'Maks. Basma Yüksekliği' => '5.5 mss (1 m³/h)',
                    'Partikül Geçirgenliği' => 'Maks. 30 mm',
                    'Daldırma Derinliği' => 'Maks. 7 m',
                    'Kablo' => '10 metre (flatörlü)',
                    'Maks. sıvı sıcaklığı' => '30 °C',
                    'Ölçüler (G × U × Y)' => '220 × 160 × 340 mm',
                    'Ağırlık' => '4 kg',
                    'Koruma' => 'Dahili termik, otomatik reset',
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
                    'alt' => $name.($index === 0 ? '' : ' — performans grafiği'),
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
                'external_sku' => 'HD500',
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
        $sources = [
            database_path('seed-assets/troy/hd500-1.jpg'),
            database_path('seed-assets/troy/hd500-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak HD 500 görseli yok: '.$source);
            }
            $target = 'products/sumak/hd500-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak HD 500 Drenaj Dalgıç Pompa Açık (Vortex) Çarklı Monofaze 220V 370 Watt</h2>
<p>Sumak HD 500, plastik motor gövdeli, açık (vortex / Noryl) çarklı, flatörlü drenaj dalgıç pompasıdır. Lifli katı partikül içeren sularda (maks. 30 mm) bodrum, asansör boşluğu, birikinti ve süs havuzu tahliyesinde kullanılır. Dahili termik korumalı ve otomatik resetlidir. 220 V monofaze ev elektriği ile çalışır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak HD 500</td></tr>
<tr><td>Motor gücü</td><td>0.50 HP (0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Akım</td><td>1.1 A</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Kondansatör</td><td>7 µF</td></tr>
<tr><td>Çıkış</td><td>1" / 1¼" / 1½" universal</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h</td></tr>
<tr><td>Maks. basma</td><td>5.5 mss (1 m³/h)</td></tr>
<tr><td>Partikül</td><td>Maks. 30 mm</td></tr>
<tr><td>Daldırma</td><td>Maks. 7 m</td></tr>
<tr><td>Kablo</td><td>10 m, flatörlü</td></tr>
<tr><td>Ölçüler</td><td>220 × 160 × 340 mm</td></tr>
<tr><td>Ağırlık</td><td>4 kg</td></tr>
</tbody>
</table>

<h3>Performans özeti</h3>
<table>
<thead>
<tr><th>Debi</th><th>Dikey basma</th><th>Yatay basma (yaklaşık)</th></tr>
</thead>
<tbody>
<tr><td>1 m³/h</td><td>5.5 mss</td><td>55 m</td></tr>
<tr><td>2 m³/h</td><td>5 mss</td><td>50 m</td></tr>
<tr><td>3 m³/h</td><td>4.5 mss</td><td>45 m</td></tr>
<tr><td>4 m³/h</td><td>4 mss</td><td>40 m</td></tr>
<tr><td>6 m³/h</td><td>3 mss</td><td>30 m</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Bodrum kat ve asansör boşluğu su tahliyesi</li>
<li>Birikinti ve drenaj suları</li>
<li>Şelale, süs havuzu ve benzeri uygulamalar</li>
<li>Yerleşim alanı kirli / partiküllü su boşaltımı</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Flatör serbest hareket edecek şekilde konumlandırın; pompayı kuru çalıştırmayın. Maksimum sıvı sıcaklığı 30 °C, maksimum daldırma derinliği 7 m’dir.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompaları</a> kategorisine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
