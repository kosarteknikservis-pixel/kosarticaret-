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
    private const SLUG = 'winpo-wnp-400-aw-gf-gizli-flatorlu-drenaj-dalgic-pompa-7mss-7m3h-monofaze-220v';

    private const PRICE = 5499.00;

    private const TRENDYOL_CATEGORY_ID = '2600';

    private const TRENDYOL_CATEGORY_NAME = 'Su Motoru ve Pompa';

    public function up(): void
    {
        $brand = Brand::query()->where('slug', 'winpo')->first();
        if ($brand === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('Winpo markası yok.');
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
        $name = 'Winpo WNP 400 AW GF Gizli Flatörlü Drenaj Dalgıç Pompa 7 mss 7 m³/h Monofaze 220V';

        $product = Product::query()->where('slug', self::SLUG)->first();

        Product::withoutEvents(function () use ($product, $brand, $categoryIds, $paths, $name): void {
            $payload = [
                'slug' => self::SLUG,
                'sku' => 'WNP400AWGF',
                'barcode' => filled($product?->barcode) ? $product->barcode : 'WNP400AWGF',
                'name' => $name,
                'short_description' => 'Winpo WNP 400 AW GF gizli flatörlü drenaj dalgıç pompa: 0.5 HP (0.37 kW), 220 V. Debi 6–8 m³/h, maks. 7 mss. Çıkış 1¼", 10 m kablo.',
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
                'meta_description' => 'Winpo WNP 400 AW GF: gizli flatörlü drenaj dalgıç pompa, 0.5 HP, 220 V. 7 mss, 6–8 m³/h, 1¼" çıkış. Temiz ve az kirli su.',
                'weight_kg' => $product?->weight_kg !== null && (float) $product->weight_kg > 0
                    ? $product->weight_kg
                    : 6.00,
                'width_cm' => $product?->width_cm ?: 22,
                'height_cm' => $product?->height_cm ?: 35,
                'depth_cm' => $product?->depth_cm ?: 20,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Winpo',
                    'Model' => 'WNP 400 AW GF',
                    'Tip' => 'Gizli flatörlü drenaj dalgıç pompa',
                    'Motor Gücü (HP)' => '0.50 HP',
                    'Motor Gücü (kW)' => '0.37 kW',
                    'Voltaj' => '220 V - 50 Hz',
                    'Devir' => '2900 d/d',
                    'Maks. Debi' => '6–8 m³/h',
                    'Maks. Basma Yüksekliği' => '7 mss',
                    'Maks. Çalışma Derinliği' => '5 m',
                    'Çıkış Bağlantı Çapı' => '1.1/4"',
                    'Maks. Partikül Çapı' => '35 mm',
                    'Kablo' => '10 m (fişli)',
                    'Fan' => 'Noryl',
                    'Flatör' => 'Gizli (otomatik / manuel ayarlanabilir)',
                    'Sargı' => 'Bakır',
                    'Maks. sıvı sıcaklığı' => '35 °C',
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
                'external_sku' => 'WNP400AWGF',
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
            database_path('seed-assets/winpo/wnp400awgf-1.jpg'),
            database_path('seed-assets/winpo/wnp400awgf-2.jpg'),
        ];

        $paths = [];
        foreach ($sources as $i => $source) {
            if (! is_file($source)) {
                throw new \RuntimeException('Winpo WNP 400 AW GF görseli yok: '.$source);
            }
            $target = 'products/winpo/wnp400awgf-'.($i + 1).'.jpg';
            Storage::disk('public')->put($target, (string) file_get_contents($source));
            $paths[] = $target;
        }

        return $paths;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Winpo WNP 400 AW GF Gizli Flatörlü Drenaj Dalgıç Pompa 7 mss 7 m³/h Monofaze 220V</h2>
<p>Winpo WNP 400 AW GF, gizli flatörlü drenaj dalgıç pompasıdır. Temiz ve az kirli suların boşaltılmasında kullanılır. Garaj, depo, havuz birikintisi ve ev tipi kuyu uygulamalarına uygundur. Aşındırıcı, asitli veya kimyasal sıvılarda kullanılmamalıdır. Motor soğuması için pompa tamamen su altında çalıştırılmalıdır.</p>

<h3>Teknik özellikler</h3>
<table>
<thead>
<tr><th>Özellik</th><th>Değer</th></tr>
</thead>
<tbody>
<tr><td>Model</td><td>Winpo WNP 400 AW GF</td></tr>
<tr><td>Motor gücü</td><td>0.50 HP (0.37 kW)</td></tr>
<tr><td>Voltaj</td><td>220 V - 50 Hz</td></tr>
<tr><td>Devir</td><td>2900 d/d</td></tr>
<tr><td>Maks. debi</td><td>6–8 m³/h</td></tr>
<tr><td>Maks. basma</td><td>7 mss</td></tr>
<tr><td>Çalışma derinliği</td><td>Maks. 5 m</td></tr>
<tr><td>Çıkış</td><td>1.1/4"</td></tr>
<tr><td>Partikül</td><td>Maks. 35 mm</td></tr>
<tr><td>Kablo</td><td>10 m fişli</td></tr>
<tr><td>Fan</td><td>Noryl</td></tr>
<tr><td>Flatör</td><td>Gizli (otomatik / manuel)</td></tr>
<tr><td>Sıvı sıcaklığı</td><td>Maks. 35 °C</td></tr>
</tbody>
</table>

<h3>Performans özeti</h3>
<table>
<thead>
<tr><th>Debi</th><th>Dikey basma</th><th>Yatay (yaklaşık)</th></tr>
</thead>
<tbody>
<tr><td>0 m³/h</td><td>7 mss</td><td>70 m</td></tr>
<tr><td>2 m³/h</td><td>5 mss</td><td>50 m</td></tr>
<tr><td>4 m³/h</td><td>4 mss</td><td>40 m</td></tr>
<tr><td>6 m³/h</td><td>2 mss</td><td>20 m</td></tr>
</tbody>
</table>

<h3>Kullanım alanları</h3>
<ul>
<li>Garaj, depo ve bodrum birikinti tahliyesi</li>
<li>Havuz / süs suyu boşaltımı</li>
<li>Temiz ve az kirli su drenajı</li>
<li>Ev tipi kuyu su temini (uygun koşullarda)</li>
</ul>

<h3>Kurulum notu</h3>
<p>Yalnızca 220 V monofaze besleme ile çalışır. Gizli flatör otomatik veya manuel moda ayarlanabilir. Pompayı kuru çalıştırmayın; maksimum çalışma derinliği 5 m’dir.</p>

<p>Benzer ürünler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompaları</a> kategorisine bakın. Marka özeti: <a href="/marka/winpo">Winpo</a>.</p>
HTML;
    }
};
