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
    private const SLUG = 'troy-hmj-15-dokum-govdeli-jet-pompa-1-hp-49-mss-4-m3-h';

    private const PRICE = 8599.00;

    private const TRENDYOL_CATEGORY_ID = '2600';

    private const TRENDYOL_CATEGORY_NAME = 'Su Motoru ve Pompa';

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'troy')->first();
        if ($brand === null) {
            $seo = config('brand_seo.troy');
            if (! is_array($seo)) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('Troy markası / brand_seo yok.');
            }

            $brand = Brand::query()->firstOrCreate(
                ['slug' => 'troy'],
                [
                    'name' => 'Troy',
                    'description' => $seo['description'],
                    'meta_title' => $seo['meta_title'],
                    'meta_description' => $seo['meta_description'],
                    'faq' => $seo['faq'],
                    'featured' => false,
                    'active' => true,
                    'sort_order' => ((int) Brand::query()->max('sort_order')) + 1,
                ]
            );
        }

        $categorySlugs = ['jet-pompalar-derinden-emisli', 'su-pompalari'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'jet-pompalar-derinden-emisli')->value('id')
                ?: Category::query()->where('slug', 'su-pompalari')->value('id');
            if (! $fallback) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('Jet pompa kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Troy HMJ 15 Döküm Gövdeli Jet Pompa 1 HP 49 mss 4 m³/h';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'HMJ-15',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'HMJ15JET',
                'name' => $name,
                'short_description' => 'Troy HMJ 15 döküm gövdeli jet pompa: 1 HP (0,75 kW), 220 V monofaze. Maks. 41–50 mss, 0–5 m³/h, 9 m emiş.',
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
                'meta_description' => 'Troy HMJ 15 döküm gövdeli jet pompa: 1 HP, 220 V, maks. 49 mss, 4 m³/h. Depo ve sığ kuyu emişi için teknik tablo.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 12.00,
                'width_cm' => $product?->width_cm ?: 25,
                'height_cm' => $product?->height_cm ?: 30,
                'depth_cm' => $product?->depth_cm ?: 40,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Troy',
                    'Model' => 'HMJ 15',
                    'Tip' => 'Döküm gövdeli jet pompa',
                    'Motor Gücü (HP)' => '1 HP',
                    'Motor Gücü (kW)' => '0,75 kW',
                    'Voltaj' => '220 V monofaze',
                    'Maks. Basma Yüksekliği' => '41–50 mss',
                    'Maks. Debi' => '0–5 m³/h',
                    'Emiş Derinliği' => '9 Metre',
                    'Emme Giriş Çapı' => '1" - 25 mm',
                    'Basma Çıkış Çapı' => '1" - 25 mm',
                    'Gövde' => 'Döküm',
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
                'external_sku' => 'HMJ-15',
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
            database_path('seed-assets/troy/hmj-15-jet-1.jpg'),
            database_path('seed-assets/troy/hmj-15-jet-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Troy HMJ 15 jet görseli yok: '.$source);
            }
            $target = 'products/troy/hmj-15-jet-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Troy HMJ 15 Döküm Gövdeli Jet Pompa 1 HP</h2>
<p>Troy HMJ 15, 1 HP (0,75 kW) monofaze döküm gövdeli jet pompadır. Depo veya sığ kuyudan temiz su emişi ve basma için kullanılır. Tanklı paket hidrofor değildir; yalnız pompa gövdesidir. Basınçlandırma için genleşme tankı ve basınç şalteri ayrıca seçilir.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka / Model</td><td>Troy HMJ 15</td></tr>
<tr><td>Motor gücü</td><td>1 HP (0,75 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V monofaze</td></tr>
<tr><td>Maks. basma yüksekliği</td><td>41–50 mss</td></tr>
<tr><td>Maks. debi</td><td>0–5 m³/h</td></tr>
<tr><td>Emiş derinliği</td><td>9 metre</td></tr>
<tr><td>Emme / basma</td><td>1" – 1" (25 mm)</td></tr>
<tr><td>Gövde</td><td>Döküm</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Depodan temiz su emişi</li>
<li>Sığ kuyu ve sarnıç uygulamaları</li>
<li>Bahçe sulama destek hattı</li>
<li>Tank + şalter ile ev tipi hidrofor kurulumu</li>
</ul>

<h3>Kurulum notu</h3>
<p>İlk çalışmada pompa gövdesine su doldurulmalıdır. Emiş hattında dip klapesi / çekvalf kullanın; emiş borusu daraltılmamalıdır. Susuz çalıştırmayın. Sıcak su hattı için uygun değildir.</p>

<p>Tanklı paket arıyorsanız <a href="/urun/troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli">Troy HMJ H 15 paket hidrofor</a> ve <a href="/kategoriler/su-pompalari/jet-pompalar-derinden-emisli">jet pompalar</a> kategorisine bakın. Marka özeti: <a href="/marka/troy">Troy</a>.</p>
HTML;
    }
};
