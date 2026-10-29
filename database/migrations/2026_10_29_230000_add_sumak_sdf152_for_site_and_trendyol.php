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
    private const SLUG = 'sumak-sdf15-2-komple-paslanmaz-foseptik-dalgic-drenaj-pompa-220-volt-1-5-hp';

    private const PRICE = 26999.00;

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

        $categorySlugs = ['foseptik-dalgic-pompa', 'paslanmaz-drenaj-dalgic-pompa', 'dalgic-pompalar'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'foseptik-dalgic-pompa')->value('id')
                ?: Category::query()->where('slug', 'dalgic-pompalar')->value('id');
            if (! $fallback) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('foseptik-dalgic-pompa kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Sumak SDF15/2 Komple Paslanmaz Foseptik Dalgıç Drenaj Pompa 220V 1.5 HP';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'SDF152',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'SDF152',
                'name' => $name,
                'short_description' => 'Sumak SDF15/2 komple paslanmaz foseptik dalgıç pompa: 1.5 HP (1.1 kW), 220 V, flatör + pano dahil. Çıkış 2", maks. ~11 mss.',
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
                'meta_description' => 'Sumak SDF15/2: 1.5 HP paslanmaz foseptik/drenaj dalgıç pompa, 220 V, flatör ve kumanda panosu dahil. Teknik tablo.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 16.00,
                'width_cm' => $product?->width_cm ?: 19,
                'height_cm' => $product?->height_cm ?: 40,
                'depth_cm' => $product?->depth_cm ?: 22,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'SDF 15/2',
                    'Tip' => 'Komple paslanmaz foseptik / drenaj dalgıç pompa',
                    'Motor Gücü (HP)' => '1.5 HP',
                    'Motor Gücü (kW)' => '1.1 kW',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Akım' => '6.5 A',
                    'Devir' => '2900 d/d',
                    'Çıkış Ölçüsü' => '2" - 50 mm',
                    'Maks. Debi' => '16–25 m³/h',
                    'Maks. Basma Yüksekliği' => '11–20 mss',
                    'Gövde / Çark / Mil' => 'AISI 304 INOX',
                    'Kablo' => '10 metre',
                    'Paket' => 'Flatör + kumanda panosu dahil',
                    'Maks. sıvı sıcaklığı' => '30 °C',
                    'Ölçüler (G × U × Y)' => '185 × 215 × 395 mm',
                    'Ağırlık' => '16 kg',
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
                    'alt' => $name.($index === 0 ? '' : ' — teknik görünüm'),
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
                'external_sku' => 'SDF152',
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
            database_path('seed-assets/sumak/sdf15-2-1.jpg'),
            database_path('seed-assets/sumak/sdf15-2-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak SDF15/2 görseli yok: '.$source);
            }
            $target = 'products/sumak/sdf15-2-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak SDF15/2 Komple Paslanmaz Foseptik Dalgıç Drenaj Pompa 220V 1.5 HP</h2>
<p>Sumak SDF15/2, 1.5 HP (1.1 kW) monofaze komple paslanmaz foseptik / drenaj dalgıç pompasıdır. Az kirli ve atık su tahliyesinde kullanılır. Flatör ve kumanda panosu pakete dahildir. Gövde, çark ve mil AISI 304 paslanmaz çeliktir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SDF 15/2</td></tr>
<tr><td>Motor gücü</td><td>1.5 HP (1.1 kW)</td></tr>
<tr><td>Voltaj / akım</td><td>220 V monofaze / 6.5 A</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Çıkış</td><td>2" (50 mm)</td></tr>
<tr><td>Maks. debi</td><td>16–25 m³/h</td></tr>
<tr><td>Maks. basma</td><td>11–20 mss</td></tr>
<tr><td>Malzeme</td><td>AISI 304 (gövde, çark, mil)</td></tr>
<tr><td>Kablo</td><td>10 metre</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>maks. 30 °C</td></tr>
<tr><td>Ölçü / ağırlık</td><td>185 × 215 × 395 mm / 16 kg</td></tr>
<tr><td>Paket</td><td>Flatör + kumanda panosu</td></tr>
</tbody>
</table>

<h3>Debi / basma örneği</h3>
<table>
<thead>
<tr><th>Debi</th><th>Dikey basma</th></tr>
</thead>
<tbody>
<tr><td>3 m³/h</td><td>11 m</td></tr>
<tr><td>6 m³/h</td><td>9 m</td></tr>
<tr><td>9 m³/h</td><td>8 m</td></tr>
<tr><td>11 m³/h</td><td>7 m</td></tr>
<tr><td>16.5 m³/h</td><td>3.5 m</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Kanalizasyon / foseptik su tahliyesi</li>
<li>Yağmur suyu drenajı</li>
<li>Az kirli su boşaltımı</li>
<li>Küçük şelale / süs suyu uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>Pompa sıvı altında çalıştırılmalıdır. Flatör serbest hareket edecek şekilde bağlanmalıdır. Kumanda panosu üretici şemasına göre bağlanır. Topraklama yapılmalıdır. İri katı parçalı veya yüksek sıcaklıklı sıvılar için uygun değildir.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a> ve <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj</a> kategorilerine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
