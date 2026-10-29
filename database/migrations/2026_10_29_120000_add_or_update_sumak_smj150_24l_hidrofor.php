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
    private const SLUG = 'sumak-smj-150-hidrofor-4-kat-6-daire-24-litre-tankli-hidrofor';

    private const PRICE = 17999.00;

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

        $categorySlugs = ['hidroforlar', 'sumak-hidrofor', 'ev-tipi-hidroforlar'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'hidroforlar')->value('id');
            if (! $fallback) {
                throw new \RuntimeException('hidroforlar kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Sumak SMJ 150 Hidrofor 4 Kat 6 Daire 24 Litre Küre Tanklı';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'SMJH-150',
                'name' => $name,
                'short_description' => 'Sumak SMJ 150 paket hidrofor: 1.5 HP (1.1 kW), 220 V, 24 litre küre tank. 4 kat / 6 daire konut basınçlandırması.',
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
                'meta_description' => 'Sumak SMJ 150 paket hidrofor: 1.5 HP, 220 V, 24 litre küre tank, 4 kat 6 daire. Jet pompalı ev tipi hidrofor; teknik tablo ve kurulum notu.',
                'weight_kg' => 22,
                'width_cm' => 35.0,
                'height_cm' => 72.0,
                'depth_cm' => 63.0,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'barcode' => filled($product?->barcode) ? $product->barcode : 'SMJH15024L',
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'SMJ 150',
                    'Tip' => 'Jet pompalı paket hidrofor',
                    'Motor Gücü (HP)' => '1.5 HP',
                    'Motor Gücü (kW)' => '1.1 kW',
                    'Voltaj' => '220 V monofaze',
                    'Maks. Debi' => '0–5 m³/h',
                    'Maks. Basma Yüksekliği' => '41–50 mss',
                    'Tank' => '24 litre küre tip genleşme tankı',
                    'Kat / Daire' => '4 kat / 6 daire',
                    'Maks. Emiş' => '9 metre',
                    'Giriş / Çıkış' => '1" (25 mm)',
                    'Devir' => '2900 d/d',
                    'Ölçüler (G × U × Y)' => '350 × 630 × 720 mm',
                    'Paket içeriği' => '1.5 HP jet pompa, 24 litre küre tank, 5 yollu, basınç şalteri, manometre',
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
                'external_sku' => 'SMJH-150',
                'channel_price' => self::PRICE,
                'external_product_id' => null,
                'last_error' => null,
            ],
        );
    }

    public function down(): void
    {
        // Mevcut katalog kaydı olabilir; silme yok.
    }

    /**
     * @return list<string>
     */
    private function publishImages(): array
    {
        $sources = [
            database_path('seed-assets/sumak/smj-150-1.jpg'),
            database_path('seed-assets/sumak/smj-150-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak SMJ 150 görseli yok: '.$source);
            }
            $target = 'products/sumak/smj-150-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak SMJ 150 Hidrofor 4 Kat 6 Daire 24 Litre Küre Tanklı</h2>
<p>Sumak SMJ 150, 1.5 HP (1.1 kW) kendinden emişli jet pompa ile 24 litre küre tip genleşme tankının birleştiği monofaze paket hidrofordur. 4 kat / 6 daireye kadar konutlarda şebeke veya depo basıncını yükseltmek için kullanılır. Su talebi başladığında otomatik devreye girer; talep bitince basınç şalteri pompayı durdurur.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka</td><td>Sumak</td></tr>
<tr><td>Model</td><td>SMJ 150</td></tr>
<tr><td>Motor gücü</td><td>1.5 HP (1.1 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>41–50 mss</td></tr>
<tr><td>Kat / daire</td><td>4 kat / 6 daire</td></tr>
<tr><td>Maks. emiş</td><td>9 metre</td></tr>
<tr><td>Giriş / çıkış</td><td>1" (25 mm)</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Genleşme tankı</td><td>24 litre küre</td></tr>
<tr><td>Ölçüler (G × U × Y)</td><td>350 × 630 × 720 mm</td></tr>
</tbody>
</table>

<h3>Paket içeriği</h3>
<ul>
<li>1.5 HP jet pompa</li>
<li>24 litre küre tip genleşme tankı</li>
<li>5 yollu bağlantı</li>
<li>Basınç şalteri</li>
<li>Manometre</li>
</ul>

<h3>Kullanım alanları</h3>
<ul>
<li>4 kata / 6 daireye kadar apartman ve müstakil ev basınçlandırması</li>
<li>Depodan temiz su basma</li>
<li>Villa ve yazlık su basıncı desteği</li>
<li>Bahçe sulama destek hattı</li>
</ul>

<h3>Kurulum notu</h3>
<p>İlk çalışmada pompa gövdesine su doldurulmalıdır. Depo veya sığ kuyu emişinde dip klapesi / çekvalf kullanın; depoda su bitmesine karşı seviye flatörü önerilir. Genleşme tankı ön gaz basıncı periyodik kontrol edilmelidir. Sıcak su hattı için uygun değildir.</p>

<p>Benzer paketler için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> ve <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak hidrofor</a> kategorilerine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
