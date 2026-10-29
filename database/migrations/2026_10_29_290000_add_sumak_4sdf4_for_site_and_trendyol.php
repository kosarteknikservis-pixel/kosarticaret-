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
    private const SLUG = 'sumak-4sdf4-07-hp-220v-keson-dalgici-1-cikisli';

    private const PRICE = 14199.00;

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

        $categorySlugs = ['keson-kuyu-pompa', 'temiz-su-dalgic-pompa', 'dalgic-pompalar'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'keson-kuyu-pompa')->value('id')
                ?: Category::query()->where('slug', 'temiz-su-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'dalgic-pompalar')->value('id');
            if (! $fallback) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('keson-kuyu-pompa kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Sumak 4SDF4 0.7 HP 220V Keson Dalgıcı 1" Çıkışlı Flatörlü';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => '4SDF4',
                'barcode' => filled($product?->barcode) ? $product->barcode : '4SDF4',
                'name' => $name,
                'short_description' => 'Sumak 4SDF4 keson dalgıç pompa: 0.7 HP (0.5 kW), 220 V, 1" çıkış. Maks. 40 mss, 4.3 m³/h. AISI 304 gövde, flatörlü, 10 m kablo.',
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
                'meta_description' => 'Sumak 4SDF4: 0.7 HP 220 V keson / 4" kuyu dalgıç pompa. 40 mss, 4.3 m³/h, 1" çıkış, flatörlü paslanmaz gövde.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 7.20,
                'width_cm' => $product?->width_cm ?: 10,
                'height_cm' => $product?->height_cm ?: 39,
                'depth_cm' => $product?->depth_cm ?: 10,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => '4SDF4',
                    'Tip' => '4" keson / geniş kuyu dalgıç pompa (flatörlü)',
                    'Motor Gücü (HP)' => '0.7 HP',
                    'Motor Gücü (kW)' => '0.5 kW',
                    'Voltaj' => '220 Volt (Monofaze) / 50 Hz',
                    'Maks. Basma Yüksekliği' => '40 metre',
                    'Maks. Debi' => '4.3 m³/h',
                    'Çıkış Çapı' => '1" (~32 mm)',
                    'Kablo' => '10 metre',
                    'Pompa Dayanma Basıncı' => '6 bar',
                    'Sıvı Sıcaklığı' => '0–30 °C',
                    'Koruma / İzolasyon' => 'IP68 / Class F',
                    'Gövde / Mil' => 'AISI 304 paslanmaz çelik',
                    'Çark' => 'Polikarbon',
                    'Salmastra' => 'Seramik / karbon',
                    'Flatör' => 'Dahili seviye flatörü',
                    'Ölçüler (Çap × Yükseklik)' => '100 × 390 mm',
                    'Ağırlık' => '7.2 kg',
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
                    'alt' => $name.($index === 0 ? '' : ' — ürün görünümü'),
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
                'external_sku' => '4SDF4',
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
            database_path('seed-assets/sumak/4sdf4-1.jpg'),
            database_path('seed-assets/sumak/4sdf4-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak 4SDF4 görseli yok: '.$source);
            }
            $target = 'products/sumak/4sdf4-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak 4SDF4 0.7 HP 220V Keson Dalgıcı 1" Çıkışlı Flatörlü</h2>
<p>Sumak 4SDF4, 4 inç (100 mm) keson ve sondaj kuyularından temiz su çekmek için tasarlanmış paslanmaz çelik gövdeli, flatörlü yüksek basmalı dalgıç pompadır. Yağ soğutmalı motor, AISI 304 gövde/mil ve dahili seviye flatörü ile susuz çalışmaya karşı korunur. Evsel, tarımsal ve ticari temiz su uygulamalarına uygundur.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak 4SDF4</td></tr>
<tr><td>Motor gücü</td><td>0.7 HP (0.5 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze / 50 Hz</td></tr>
<tr><td>Maks. basma</td><td>40 metre</td></tr>
<tr><td>Maks. debi</td><td>4.3 m³/h</td></tr>
<tr><td>Çıkış</td><td>1"</td></tr>
<tr><td>Kablo</td><td>10 m</td></tr>
<tr><td>Dayanma basıncı</td><td>6 bar</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>0–30 °C</td></tr>
<tr><td>Koruma</td><td>IP68 / Class F</td></tr>
<tr><td>Gövde / mil</td><td>AISI 304</td></tr>
<tr><td>Çark</td><td>Polikarbon</td></tr>
<tr><td>Ölçüler</td><td>100 × 390 mm</td></tr>
<tr><td>Ağırlık</td><td>7.2 kg</td></tr>
<tr><td>Flatör</td><td>Dahili</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>4" ve üzeri keson / sondaj kuyusu temiz su çekimi</li>
<li>Bahçe, sera ve amatör sulama</li>
<li>Depo / villa su temini</li>
<li>Süs havuzu ve fıskiye sirkülasyonu</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Flatör serbest hareket edecek şekilde konumlandırın. Dar kuyuda flatörsüz kullanımda susuz çalışma koruması için pano önerilir. Temiz su içindir; yoğun çamurlu veya iri partiküllü akışkanlarda kullanmayın.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/keson-kuyu-pompa">keson kuyu pompaları</a> kategorisine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
