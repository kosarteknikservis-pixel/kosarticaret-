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
    private const SLUG = 'sumak-stn2000g-sintine-dalgic-pompa-6-mss-4500-litre-h-12-volt-manuel';

    private const PRICE = 5999.00;

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
        $name = 'Sumak STN2000G Sintine Dalgıç Pompa 6 mss 4500 litre/h 12 Volt Manuel';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'STN2000G',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'STN2000G',
                'name' => $name,
                'short_description' => 'Sumak STN2000G manuel sintine dalgıç pompa: 12 V DC, 96 W, 8 A. Maks. 6 mss, 4500 L/h. Tekne, yat ve kamp tahliyesi.',
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
                'meta_description' => 'Sumak STN2000G: 12 V manuel sintine dalgıç pompa. 96 W, 6 mss, 4500 L/h. Tekne, yat ve kamp su tahliyesi.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 1.50,
                'width_cm' => $product?->width_cm ?: 11,
                'height_cm' => $product?->height_cm ?: 16,
                'depth_cm' => $product?->depth_cm ?: 14,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'STN2000 G',
                    'Tip' => 'Manuel sintine dalgıç pompa',
                    'Motor Gücü' => '96 W',
                    'Voltaj' => '12 V DC',
                    'Akım' => '8 A',
                    'Maks. Basma Yüksekliği' => '6 mss',
                    'Maks. Debi' => '4500 litre/h',
                    'Çıkış' => '29 mm',
                    'Kablo' => '1 m',
                    'Ölçüler (G × U × Y)' => '108 × 138 × 154 mm',
                    'Kontrol' => 'Manuel',
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
                'external_sku' => 'STN2000G',
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
            database_path('seed-assets/sumak/stn2000g-1.jpg'),
            database_path('seed-assets/sumak/stn2000g-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak STN2000G görseli yok: '.$source);
            }
            $target = 'products/sumak/stn2000g-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak STN2000G Sintine Dalgıç Pompa 6 mss 4500 litre/h 12 Volt Manuel</h2>
<p>Sumak STN2000G, 12 V DC manuel sintine dalgıç pompasıdır. Deniz suyu, birikintili su ve temiz su tahliyesinde kullanılır. Motor dairesi ve tekne sintinesi için uygundur; 12 V akü beslemesi ile kamp ve elektrik olmayan alanlarda da kullanılabilir. Kontrol manueldir (otomatik flatör yoktur).</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Sumak STN2000 G</td></tr>
<tr><td>Motor gücü</td><td>96 W</td></tr>
<tr><td>Voltaj</td><td>12 V DC</td></tr>
<tr><td>Akım</td><td>8 A</td></tr>
<tr><td>Maks. basma</td><td>6 mss</td></tr>
<tr><td>Maks. debi</td><td>4500 litre/h</td></tr>
<tr><td>Çıkış</td><td>29 mm</td></tr>
<tr><td>Kablo</td><td>1 m</td></tr>
<tr><td>Ölçüler</td><td>108 × 138 × 154 mm</td></tr>
<tr><td>Kontrol</td><td>Manuel</td></tr>
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

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/sintine-pompasi">sintine pompaları</a> kategorisine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
