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
    private const SLUG = 'sumak-hd-1700-2-flatorlu-kirli-su-foseptik-dalgic-pompa-15-hp';

    private const PRICE = 10999.00;

    private const TRENDYOL_PRICE = 12599.00;

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

        $categorySlugs = ['foseptik-dalgic-pompa', 'kirli-su-dalgic-pompa', 'drenaj-dalgic-pompa', 'dalgic-pompalar'];
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
        $name = 'Sumak HD 1700/2 Flatörlü Kirli Su Foseptik Açık Çarklı Dalgıç Pompa 1.5 HP';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'HD1700-2',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'HD1700-2',
                'name' => $name,
                'short_description' => 'Sumak HD 1700/2 flatörlü foseptik/kirli su dalgıç pompa: 1.5 HP (1.1 kW), 220 V, AISI 304 gövde, vortex çark, 2" çıkış. Maks. ~15 m³/h, ~16 mss. 10 m kablo.',
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
                'meta_description' => 'Sumak HD 1700/2: 1.5 HP flatörlü paslanmaz gövdeli foseptik/kirli su dalgıç pompa, 220 V, 2" çıkış. Bodrum, drenaj ve foseptik tahliyesi.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 17.00,
                'width_cm' => $product?->width_cm ?: 25,
                'height_cm' => $product?->height_cm ?: 48,
                'depth_cm' => $product?->depth_cm ?: 17,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'HD 1700/2',
                    'Tip' => 'Flatörlü foseptik / kirli su dalgıç pompa (açık / vortex çark)',
                    'Motor Gücü (HP)' => '1.5 HP',
                    'Motor Gücü (kW)' => '1.1 kW',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Akım' => '8.5 A',
                    'Devir' => '2900 d/d',
                    'Kondansatör' => '25 µF',
                    'Çıkış Ölçüsü' => '2" (50 mm)',
                    'Maks. Debi' => '11–15 m³/h',
                    'Maks. Basma Yüksekliği' => '16 mss (4 m³/h)',
                    'Partikül Geçirgenliği' => 'Maks. 15 mm',
                    'Pompa Gövdesi' => 'AISI 304 paslanmaz',
                    'Çark' => 'GG14 döküm (vortex)',
                    'Mil' => 'AISI 304 INOX',
                    'Kablo' => '10 metre (flatörlü)',
                    'Ölçüler (G × U × Y)' => '250 × 170 × 483 mm',
                    'Ağırlık' => '17 kg',
                    'Soğutma' => 'Yağ soğutmalı motor',
                    'Koruma' => 'Dahili termik, otomatik reset',
                    'Garanti' => '24 ay',
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
                    'alt' => $name.($index === 0 ? '' : ' — ölçü ve performans eğrisi'),
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
                'external_sku' => 'HD1700-2',
                'channel_price' => self::TRENDYOL_PRICE,
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
            database_path('seed-assets/troy/hd1700-2-1.jpg'),
            database_path('seed-assets/troy/hd1700-2-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak HD 1700/2 görseli yok: '.$source);
            }
            $target = 'products/sumak/hd1700-2-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak HD 1700/2 Flatörlü Kirli Su Foseptik Açık Çarklı Dalgıç Pompa 1.5 HP</h2>
<p>Sumak HD 1700/2, AISI 304 paslanmaz gövdeli, GG14 döküm vortex çarklı, flatörlü dalgıç pompadır. Lifli ve katı partiküllü (maks. 15 mm) foseptik / kirli suların basımında kullanılır. Yağ soğutmalı motor tasarımı uzun ömür ve sessiz çalışma sağlar. 220 V monofaze ev elektriği ile çalışır; 10 m kablo ve flatör ile otomatik seviye kontrolü sunar.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak HD 1700/2</td></tr>
<tr><td>Motor gücü</td><td>1.5 HP (1.1 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Akım</td><td>8.5 A</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Kondansatör</td><td>25 µF</td></tr>
<tr><td>Çıkış</td><td>2" (50 mm)</td></tr>
<tr><td>Maks. debi</td><td>11–15 m³/h</td></tr>
<tr><td>Maks. basma</td><td>16 mss (4 m³/h)</td></tr>
<tr><td>Partikül</td><td>Maks. 15 mm</td></tr>
<tr><td>Gövde / çark / mil</td><td>AISI 304 / GG14 döküm vortex / AISI 304</td></tr>
<tr><td>Kablo</td><td>10 m, flatörlü</td></tr>
<tr><td>Ölçüler</td><td>250 × 170 × 483 mm</td></tr>
<tr><td>Ağırlık</td><td>17 kg</td></tr>
</tbody>
</table>

<h3>Performans özeti</h3>
<table>
<thead>
<tr><th>Debi (m³/h)</th><th>Basma (mss)</th></tr>
</thead>
<tbody>
<tr><td>4</td><td>16</td></tr>
<tr><td>8</td><td>15</td></tr>
<tr><td>16</td><td>11</td></tr>
<tr><td>20</td><td>9.5</td></tr>
<tr><td>24</td><td>7</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Foseptik ve kirli su tahliyesi</li>
<li>Bodrum, drenaj çukuru ve birikinti suları</li>
<li>Şelale, süs havuzu ve benzeri uygulamalar (maks. ~16 mss)</li>
<li>15 mm’yi geçmeyen partiküllü sularda</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Flatör serbest hareket edecek şekilde konumlandırın; pompayı kuru çalıştırmayın. Çıkış hattı 2" (50 mm) bağlantıya uygundur.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a> ve <a href="/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa">kirli su dalgıç pompalar</a> kategorilerine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
