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

/**
 * Kaynak: https://www.kampa.com.tr/urun/skld12g-kalem-dalgic-pompasi-3-mss-720-litre-h-manuel
 * Teknik tablo (Kampa): 18 W, 12 V DC, 1.5 A, 3 mss, 720 L/h, çıkış 16 mm, H 208 mm, çap 52 mm, kablo 3 m.
 * Satış fiyatı kullanıcı talebi: 1999 TL.
 */
return new class extends Migration
{
    private const SLUG = 'sumak-skld12g-kalem-dalgic-pompasi-3-mss-720-litre-h-12-volt-manuel';

    private const PRICE = 1999.00;

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

        $categorySlugs = ['sintine-pompasi', 'dalgic-pompalar', 'su-pompalari'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'sintine-pompasi')->value('id')
                ?: Category::query()->where('slug', 'dalgic-pompalar')->value('id');
            if (! $fallback) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('sintine-pompasi kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Sumak SKLD12G Kalem Dalgıç Pompası 3 mss 720 litre/h 12 Volt Manuel';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'SKLD12G',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'SKLD12G',
                'name' => $name,
                'short_description' => 'Sumak SKLD12G manuel kalem dalgıç / sintine pompa: 12 V DC, 18 W, 1.5 A. Maks. 3 mss, 720 L/h. Tekne, yat ve kamp tahliyesi.',
                'description' => $this->descriptionHtml(),
                'brand_id' => $brand->id,
                'price' => self::PRICE,
                'compare_at_price' => null,
                'stock' => max(1, (int) ($product?->stock ?? 3)),
                'featured' => false,
                'is_active' => true,
                'marketplace_enabled' => true,
                'image' => $paths[0],
                'image_alt' => $name,
                'meta_title' => $name,
                'meta_description' => 'Sumak SKLD12G: 12 V manuel kalem dalgıç pompa. 18 W, 3 mss, 720 L/h. Tekne, yat ve kamp su tahliyesi.',
                // Ağırlık kaynakta yok; ölçü: çap 52 mm, yükseklik 208 mm (Kampa tablo).
                'weight_kg' => $product?->weight_kg,
                'width_cm' => $product?->width_cm ?: 6,
                'height_cm' => $product?->height_cm ?: 21,
                'depth_cm' => $product?->depth_cm ?: 6,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'SKLD12 G',
                    'Tip' => 'Manuel kalem / sintine dalgıç pompa',
                    'Motor Gücü' => '18 W',
                    'Voltaj' => '12 V DC',
                    'Akım' => '1.5 A',
                    'Devir' => '12.000 d/dk',
                    'Maks. Basma Yüksekliği' => '3 mss',
                    'Maks. Debi' => '720 litre/h',
                    'Çıkış' => '16 mm',
                    'Kablo' => '3 m',
                    'Yükseklik' => '208 mm',
                    'Çap' => '52 mm',
                    'Kontrol' => 'Manuel',
                    'Garanti' => '24 Ay',
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

        // Site yayını; Trendyol gönderimi ayrıca yapılır (draft listing hazır).
        MarketplaceListing::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'channel_key' => 'trendyol',
            ],
            [
                'status' => 'draft',
                'external_sku' => 'SKLD12G',
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
            database_path('seed-assets/sumak/skld12g-1.jpg'),
            database_path('seed-assets/sumak/skld12g-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak SKLD12G görseli yok: '.$source);
            }
            $target = 'products/sumak/skld12g-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak SKLD12G Kalem Dalgıç Pompası 3 mss 720 litre/h 12 Volt Manuel</h2>
<p>Sumak SKLD12G, 12 V DC manuel kalem / sintine dalgıç pompasıdır. Deniz suyu, birikintili su ve temiz su tahliyesinde kullanılır. Tekne, yat ve motor dairesi birikintileri için uygundur; 12 V akü beslemesi ile kamp ve elektrik olmayan alanlarda da kullanılabilir. Kontrol manueldir (otomatik flatör yoktur).</p>

<h3>Teknik özellikler</h3>
<p>Kaynak teknik tablo (üretici / bayi listesi):</p>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak SKLD12 G</td></tr>
<tr><td>Motor gücü</td><td>18 W</td></tr>
<tr><td>Voltaj</td><td>12 V DC</td></tr>
<tr><td>Akım</td><td>1.5 A</td></tr>
<tr><td>Devir</td><td>12.000 d/dk</td></tr>
<tr><td>Maks. basma</td><td>3 mss</td></tr>
<tr><td>Maks. debi</td><td>720 litre/h</td></tr>
<tr><td>Çıkış</td><td>16 mm</td></tr>
<tr><td>Kablo</td><td>3 m</td></tr>
<tr><td>Yükseklik</td><td>208 mm</td></tr>
<tr><td>Çap</td><td>52 mm</td></tr>
<tr><td>Kontrol</td><td>Manuel</td></tr>
<tr><td>Garanti</td><td>24 Ay</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Tekne, yat ve balıkçı teknesi sintine tahliyesi</li>
<li>Motor dairesi birikinti suyu</li>
<li>Kamp / piknik 12 V su boşaltımı</li>
<li>Bodrum ve otopark birikinti tahliyesi (12 V besleme ile)</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 12 V DC besleme ile çalışır; 220 V şebekeye bağlanmaz. Akü kutuplarına doğru polaritede bağlayın. Manuel modeldir; seviye kontrolü için harici şalter veya flatör ayrıca gerekir. Pompa sıvı altında çalıştırılmalıdır.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/sintine-pompasi">sintine pompaları</a> kategorisine bakın. Daha yüksek debili 12 V model: <a href="/urun/sumak-stn2000g-sintine-dalgic-pompa-6-mss-4500-litre-h-12-volt-manuel">Sumak STN2000G</a>. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
