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
    private const SLUG = 'sumak-hd750f-paslanmaz-govdeli-flatorlu-foseptik-dalgic-pompa-0-50-hp-370w';

    private const PRICE = 6500.00;

    private const TRENDYOL_PRICE = 7500.00;

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
        $name = 'Sumak HD750 F Paslanmaz Gövdeli Flatörlü Foseptik Dalgıç Pompa 0.50 HP 370W';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'HD750F',
                'barcode' => filled($product?->barcode) ? $product->barcode : '2000000075075',
                'name' => $name,
                'short_description' => 'Sumak HD750 F flatörlü foseptik dalgıç pompa: 0.50 HP (370 W), 220 V, paslanmaz gövde, vortex çark, 1¼" çıkış. Maks. ~9 m³/h, 9.5 mss.',
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
                'meta_description' => 'Sumak HD750 F: 0.50 HP (370 W) paslanmaz gövdeli flatörlü foseptik dalgıç pompa, 220 V, vortex çark. Bodrum, drenaj ve foseptik tahliyesi.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 10.50,
                'width_cm' => $product?->width_cm ?: 17,
                'height_cm' => $product?->height_cm ?: 39,
                'depth_cm' => $product?->depth_cm ?: 12,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'HD750 F',
                    'Tip' => 'Flatörlü foseptik / kirli su dalgıç pompa (paslanmaz gövde, vortex çark)',
                    'Motor Gücü (HP)' => '0.50 HP',
                    'Motor Gücü (kW / W)' => '0.37 kW (370 W)',
                    'Voltaj' => '220 Volt (Monofaze)',
                    'Akım' => '2.3 A',
                    'Kondansatör' => '10 µF',
                    'Çıkış Ölçüsü' => '1¼"',
                    'Maks. Debi' => '9 m³/h (1 mss)',
                    'Maks. Basma Yüksekliği' => '9.5 mss',
                    'Partikül Geçirgenliği' => 'Maks. 10 mm',
                    'Pompa Gövdesi' => 'Paslanmaz',
                    'Çark' => 'Vortex',
                    'Soğutma' => 'Yağ soğutmalı motor',
                    'Ölçüler (G × U × Y)' => '170 × 122 × 385 mm',
                    'Ağırlık' => '10.5 kg',
                    'Otomasyon' => 'Entegre flatör (şamandıra)',
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
                    'alt' => $name.($index === 0 ? '' : ' — ürün görseli'),
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
                'external_sku' => 'HD750F',
                'channel_price' => self::TRENDYOL_PRICE,
                'channel_stock_limit' => 5,
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
            database_path('seed-assets/troy/hd750f-1.jpg'),
            database_path('seed-assets/troy/hd750f-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak HD750 F görseli yok: '.$source);
            }
            $target = 'products/sumak/hd750f-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak HD750 F Paslanmaz Gövdeli Flatörlü Foseptik Dalgıç Pompa 0.50 HP 370W</h2>
<p>Sumak HD750 F, <strong>0.50 HP (370 W)</strong> monofaze foseptik / kirli su dalgıç pompasıdır. Paslanmaz gövde ve vortex çark ile lifli ve küçük katı partikül (maks. 10 mm) içeren suların tahliyesinde kullanılır. Entegre flatörü su seviyesine göre otomatik çalışır ve durur; yağ soğutmalı motor sessiz ve uzun ömürlü kullanım sağlar.</p>

<h3>Öne çıkan özellikler</h3>
<ul>
<li><strong>0.50 HP (370 W)</strong> monofaze motor — 220 V ev elektriği</li>
<li>Paslanmaz gövde</li>
<li>Vortex çark (maks. 10 mm partikül)</li>
<li>Flatörlü otomatik çalışma</li>
<li>Yağ soğutmalı, sessiz motor</li>
<li>1¼" çıkış, taşınabilir yapı</li>
</ul>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak HD750 F</td></tr>
<tr><td>Motor gücü</td><td>0.50 HP (370 W / 0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Akım</td><td>2.3 A</td></tr>
<tr><td>Kondansatör</td><td>10 µF</td></tr>
<tr><td>Çıkış</td><td>1¼"</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>9.5 mss</td></tr>
<tr><td>Partikül geçirgenliği</td><td>Maks. 10 mm</td></tr>
<tr><td>Ölçüler (G × U × Y)</td><td>170 × 122 × 385 mm</td></tr>
<tr><td>Ağırlık</td><td>10.5 kg</td></tr>
</tbody>
</table>

<h3>Debi / basma (performans)</h3>
<table>
<thead>
<tr><th>Basma yüksekliği</th><th>Debi</th></tr>
</thead>
<tbody>
<tr><td>1 mss</td><td>9 m³/h</td></tr>
<tr><td>3 mss</td><td>7.5 m³/h</td></tr>
<tr><td>4 mss</td><td>7 m³/h</td></tr>
<tr><td>6 mss</td><td>5.5 m³/h</td></tr>
<tr><td>8 mss</td><td>4 m³/h</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Foseptik ve pis su tahliyesi</li>
<li>Bodrum / sığınak su baskını boşaltma</li>
<li>Lifli ve küçük katı parçacıklı atık su transferi</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Flatörün serbest hareket etmesini sağlayın; pompayı kuru çalıştırmayın. İlk çalıştırmada ve periyodik bakımlarda üretici talimatlarına uyun.</p>

<p>Benzer ürünler: <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a>. Marka: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
