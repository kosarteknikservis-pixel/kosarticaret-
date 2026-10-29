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
    private const SLUG = 'sumak-smac-600-wc-3-unite-foseptik-tahliye-cihazi';

    private const PRICE = 31599.00;

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

        $categorySlugs = ['foseptik-tahliye-cihazi', 'ozel-amacli-pompalar', 'su-pompalari'];
        $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');
        if ($categoryIds->isEmpty()) {
            $fallback = Category::query()->where('slug', 'foseptik-tahliye-cihazi')->value('id')
                ?: Category::query()->where('slug', 'ozel-amacli-pompalar')->value('id');
            if (! $fallback) {
                if (app()->runningUnitTests()) {
                    return;
                }

                throw new \RuntimeException('foseptik-tahliye-cihazi kategorisi yok.');
            }
            $categoryIds = collect([$fallback]);
        }

        $paths = $this->publishImages();
        $name = 'Sumak SMAC 600 WC+3 Ünite Foseptik Tahliye Cihazı';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'SMAC600',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'SMAC600',
                'name' => $name,
                'short_description' => 'Sumak SMAC 600 foseptik tahliye cihazı: 600 W, 1 klozet + 3 ünite. Bodrum WC/duş/lavabo atık suyunu basar.',
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
                'meta_description' => 'Sumak SMAC 600: 600 W WC öğütücülü foseptik tahliye cihazı. 1 klozet + 3 ünite, 18 L tank. Bodrum atık su basma.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 18.00,
                'width_cm' => $product?->width_cm ?: 45,
                'height_cm' => $product?->height_cm ?: 35,
                'depth_cm' => $product?->depth_cm ?: 25,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Sumak',
                    'Model' => 'SMAC 600',
                    'Tip' => 'WC öğütücülü foseptik tahliye cihazı',
                    'Motor Gücü' => '600 Watt',
                    'Voltaj' => '220 V - 50 Hz',
                    'Ünite Hacmi' => '18 Litre',
                    'Bağlanabilen üniteler' => '1 klozet + 1 lavabo + 1 duşakabin + 1 bide',
                    'Çark' => 'Tek bıçaklı öğütücü + açık çark',
                    'Basma Ağzı Çapı' => '32 mm',
                    'Giriş Bağlantı Çapı' => 'DN 40',
                    'Maks. dikey basma' => '9 metre',
                    'Maks. yatay basma' => '100 metre',
                    'Maks. Debi' => '16 m³/saat',
                    'Motor devri' => '2800 d/d',
                    'Ses seviyesi' => '60 dB(A)',
                    'Giren su sıcaklığı' => '35 °C ortalama',
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
                'external_sku' => 'SMAC600',
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
            database_path('seed-assets/sumak/smac-600-1.jpg'),
            database_path('seed-assets/sumak/smac-600-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Sumak SMAC 600 görseli yok: '.$source);
            }
            $target = 'products/sumak/smac-600-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Sumak SMAC 600 WC+3 Ünite Foseptik Tahliye Cihazı</h2>
<p>Sumak SMAC 600, bodrum veya eksi kotlu mekanlarda klozet, lavabo, duşakabin ve bide atık suyunu kanalizasyona basmak için kullanılan WC öğütücülü foseptik tahliye cihazıdır. Toplam 1 klozet + 3 ünite girişi vardır. Zemine kuyu kazmadan kompakt tahliye çözümü sunar.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Marka / Model</td><td>Sumak SMAC 600</td></tr>
<tr><td>Motor gücü</td><td>600 Watt</td></tr>
<tr><td>Voltaj</td><td>220 V – 50 Hz</td></tr>
<tr><td>Ünite hacmi</td><td>18 litre</td></tr>
<tr><td>Bağlantı</td><td>1 klozet + lavabo + duşakabin + bide</td></tr>
<tr><td>Çark</td><td>Tek bıçaklı öğütücü + açık çark</td></tr>
<tr><td>Basma ağzı</td><td>32 mm</td></tr>
<tr><td>Giriş çapı</td><td>DN 40</td></tr>
<tr><td>Maks. dikey basma</td><td>9 metre</td></tr>
<tr><td>Maks. yatay basma</td><td>100 metre</td></tr>
<tr><td>Maks. debi</td><td>16 m³/saat</td></tr>
<tr><td>Motor devri</td><td>2800 d/d</td></tr>
<tr><td>Ses seviyesi</td><td>60 dB(A)</td></tr>
<tr><td>Giren su sıcaklığı</td><td>ortalama 35 °C</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Bodrum kat WC ve banyo tahliyesi</li>
<li>Eksi kotlu lavabo, duş ve bide hatları</li>
<li>Kanalizasyon kotunun altında kalan mekanlar</li>
<li>Ek banyo / misafir tuvaleti uygulamaları</li>
</ul>

<h3>Kurulum notu</h3>
<p>Cihazda geri tepme ventili bulunur; atık su geri basmasına karşı koruma sağlar. Motor koruma termiği aşırı zorlanmada devreye girer. Yatay hatta yaklaşık %3 eğim önerilir. Montaj ve tesisat bağlantısı yetkili tesisatçı ile yapılmalıdır.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/foseptik-tahliye-cihazi">foseptik tahliye cihazları</a> kategorisine bakın. Marka özeti: <a href="/marka/sumak">Sumak</a>.</p>
HTML;
    }
};
