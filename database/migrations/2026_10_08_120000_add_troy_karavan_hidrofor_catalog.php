<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Category::query()->where('slug', 'karavan-hidroforu')->exists()) {
            return;
        }

        $parent = Category::query()
            ->where('slug', 'hidrofor-sistemleri')
            ->whereNull('parent_id')
            ->first();

        if ($parent === null) {
            throw new \RuntimeException('Hidrofor Sistemleri kategorisi yok.');
        }

        $seo = config('brand_seo.troy');
        if (! is_array($seo)) {
            throw new \RuntimeException('config/brand_seo.php içinde troy kaydı yok.');
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

        $paths = [];
        foreach ([
            'ts-12-8-h-1.jpg',
            'ts-12-8-h-2.jpg',
            'ts-12-12-h-1.jpg',
            'ts-12-12-h-2.jpg',
            'ts-24-15-h-1.jpg',
            'ts-24-15-h-2.jpg',
        ] as $file) {
            $paths[$file] = $this->publishImage($file);
        }

        $category = Category::query()->create([
            'slug' => 'karavan-hidroforu',
            'name' => 'Karavan Hidroforu',
            'description' => $this->categoryDescription(),
            'buying_guide' => $this->buyingGuide(),
            'faq' => $this->categoryFaq(),
            'image' => $paths['ts-12-8-h-1.jpg'],
            'parent_id' => $parent->id,
            'featured' => false,
            'show_in_menu' => false,
            'active' => true,
            'sort_order' => ((int) Category::query()->where('parent_id', $parent->id)->max('sort_order')) + 1,
            'meta_title' => '12 ve 24 Volt Karavan Hidroforu',
            'meta_description' => 'Troy 12 ve 24 V karavan hidroforları: TS 12-8 H, TS 12-12 H ve TS 24-15 H. Debi, akım ve basma yüksekliği ürün etiketinden.',
        ]);

        foreach ($this->products($paths) as $row) {
            $gallery = $row['gallery'];
            unset($row['gallery']);

            Product::withoutEvents(function () use ($row, $gallery, $brand, $category): void {
                $product = Product::query()->create([
                    ...$row,
                    'brand_id' => $brand->id,
                    'stock' => 1,
                    'rating' => 0,
                    'review_count' => 0,
                    'featured' => false,
                    'is_active' => true,
                    'marketplace_enabled' => false,
                ]);
                $product->categories()->attach($category->id);

                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $gallery['path'],
                    'alt' => $gallery['alt'],
                    'sort_order' => 1,
                ]);
            });
        }

        ImageVariant::generate($paths['ts-12-8-h-1.jpg'], array_values(array_unique([
            ...ImageVariant::presetsFor('product'),
            ...ImageVariant::presetsFor('category'),
        ])));

        foreach (['ts-12-12-h-1.jpg', 'ts-24-15-h-1.jpg'] as $file) {
            ImageVariant::generate($paths[$file], ImageVariant::presetsFor('product'));
        }

        foreach (['ts-12-8-h-2.jpg', 'ts-12-12-h-2.jpg', 'ts-24-15-h-2.jpg'] as $file) {
            ImageVariant::generate($paths[$file], ImageVariant::presetsFor('product-gallery'));
        }
    }

    public function down(): void
    {
        $slugs = [
            'troy-ts-12-8-h-12v-karavan-hidroforu',
            'troy-ts-12-12-h-12v-karavan-hidroforu',
            'troy-ts-24-15-h-24v-karavan-hidroforu',
        ];

        Product::withoutEvents(function () use ($slugs): void {
            Product::query()->whereIn('slug', $slugs)->each(function (Product $product): void {
                $product->images()->delete();
                $product->categories()->detach();
                $product->delete();
            });
        });

        Brand::query()->where('slug', 'troy')->whereDoesntHave('products')->delete();
        Category::query()->where('slug', 'karavan-hidroforu')->whereDoesntHave('products')->delete();
    }

    private function publishImage(string $file): string
    {
        $source = database_path('seed-assets/troy/'.$file);
        if (! is_file($source)) {
            throw new \RuntimeException('Troy görseli yok: '.$source);
        }

        $target = 'products/troy/'.$file;
        Storage::disk('public')->put($target, (string) file_get_contents($source));

        return $target;
    }

    /** @param  array<string, string>  $paths
     * @return list<array<string, mixed>>
     */
    private function products(array $paths): array
    {
        return [
            $this->product(
                'troy-ts-12-8-h-12v-karavan-hidroforu',
                'TS12-8H',
                'Troy TS 12-8 H 12 V Karavan Hidroforu',
                '12 V DC karavan ve tekne hidroforu. Etiket: 72 W, 6 A, 450 L/h debi, 45 m basma yüksekliği, 2,6 kg.',
                'Troy TS 12-8 H, 12 V DC karavan hidroforu. Etiket: 72 W, 6 A, 450 L/h debi, 45 m basma yüksekliği, 60 °C.',
                6510.40,
                8680.53,
                2.6,
                12.8,
                11.0,
                21.2,
                $paths['ts-12-8-h-1.jpg'],
                $paths['ts-12-8-h-2.jpg'],
                [
                    'Model' => 'TS 12-8 H',
                    'Voltaj' => '12 V DC',
                    'Motor Gücü' => '72 W',
                    'Akım' => '6 A',
                    'Maks. Debi' => '450 L/h (7,5 lt/dk)',
                    'Maks. Basma Yüksekliği' => '45 m',
                    'Maks. Sıvı Sıcaklığı' => '60 °C',
                    'Ağırlık' => '2,6 kg',
                    'Kablo Uzunluğu' => '1 m',
                    'Emiş Derinliği' => '2 m',
                    'Giriş / Çıkış Ölçüsü' => '1/2 inç',
                    'Ölçüler (G × U × Y)' => '128 × 212 × 110 mm',
                ],
                <<<'HTML'
<p>Troy TS 12-8 H, 12 V DC karavan ve tekne hidroforudur. Ürün etiketinde 72 W, 6 A, H max 45 m, Q max 450 L/h ve 2,6 kg yazar. 450 L/h, dakikada 7,5 litredir. Azami sıvı sıcaklığı 60 °C. Etiket, pompanın yanıcı sıvılarda ve patlayıcı ortamlarda kullanılmamasını söyler.</p>
<p>Troy model tablosunda emiş 2 m, kablo 1 m, giriş-çıkış 1/2 inç, ölçü 128 × 212 × 110 mm olarak geçer. Aynı 12 V hatta daha yüksek debi için <a href="/urun/troy-ts-12-12-h-12v-karavan-hidroforu">TS 12-12 H</a>, 24 V akü için <a href="/urun/troy-ts-24-15-h-24v-karavan-hidroforu">TS 24-15 H</a> vardır. Üç model <a href="/kategoriler/hidrofor-sistemleri/karavan-hidroforu">karavan hidroforu</a> kategorisindedir.</p>
HTML,
            ),
            $this->product(
                'troy-ts-12-12-h-12v-karavan-hidroforu',
                'TS12-12H',
                'Troy TS 12-12 H 12 V Karavan Hidroforu',
                '12 V DC karavan ve tekne hidroforu. Etiket: 90 W, 7,5 A, 700 L/h debi, 55 m basma yüksekliği, 2,6 kg.',
                'Troy TS 12-12 H, 12 V DC karavan hidroforu. Etiket: 90 W, 7,5 A, 700 L/h debi, 55 m basma yüksekliği, 60 °C.',
                6844.02,
                9125.36,
                2.6,
                12.8,
                11.0,
                21.2,
                $paths['ts-12-12-h-1.jpg'],
                $paths['ts-12-12-h-2.jpg'],
                [
                    'Model' => 'TS 12-12 H',
                    'Voltaj' => '12 V DC',
                    'Motor Gücü' => '90 W',
                    'Akım' => '7,5 A',
                    'Maks. Debi' => '700 L/h (11,7 lt/dk)',
                    'Maks. Basma Yüksekliği' => '55 m',
                    'Maks. Sıvı Sıcaklığı' => '60 °C',
                    'Ağırlık' => '2,6 kg',
                    'Kablo Uzunluğu' => '1 m',
                    'Emiş Derinliği' => '2 m',
                    'Giriş / Çıkış Ölçüsü' => '1/2 inç',
                    'Ölçüler (G × U × Y)' => '128 × 212 × 110 mm',
                ],
                <<<'HTML'
<p>Troy TS 12-12 H, 12 V DC karavan ve tekne hidroforudur. Etikette 90 W, 7,5 A, H max 55 m, Q max 700 L/h ve 2,6 kg yazar. 700 L/h, dakikada 11,7 litredir. Azami sıvı sıcaklığı 60 °C. Yanıcı sıvı ve patlayıcı ortam uyarısı etikettedir.</p>
<p>Troy tablosunda emiş 2 m, kablo 1 m, giriş-çıkış 1/2 inç, ölçü 128 × 212 × 110 mm. Daha düşük debili 12 V model <a href="/urun/troy-ts-12-8-h-12v-karavan-hidroforu">TS 12-8 H</a>, 24 V model <a href="/urun/troy-ts-24-15-h-24v-karavan-hidroforu">TS 24-15 H</a>. Seri <a href="/kategoriler/hidrofor-sistemleri/karavan-hidroforu">karavan hidroforu</a> sayfasında yan yanadır.</p>
HTML,
            ),
            $this->product(
                'troy-ts-24-15-h-24v-karavan-hidroforu',
                'TS24-15H',
                'Troy TS 24-15 H 24 V Karavan Hidroforu',
                '24 V DC karavan ve tekne hidroforu. Etiket: 168 W, 7 A, 900 L/h debi, 55 m basma yüksekliği, 3,2 kg.',
                'Troy TS 24-15 H, 24 V DC karavan hidroforu. Etiket: 168 W, 7 A, 900 L/h debi, 55 m basma yüksekliği, 60 °C.',
                7166.68,
                9555.58,
                3.2,
                14.2,
                12.8,
                23.3,
                $paths['ts-24-15-h-1.jpg'],
                $paths['ts-24-15-h-2.jpg'],
                [
                    'Model' => 'TS 24-15 H',
                    'Voltaj' => '24 V DC',
                    'Motor Gücü' => '168 W',
                    'Akım' => '7 A',
                    'Maks. Debi' => '900 L/h (15 lt/dk)',
                    'Maks. Basma Yüksekliği' => '55 m',
                    'Maks. Sıvı Sıcaklığı' => '60 °C',
                    'Ağırlık' => '3,2 kg',
                    'Kablo Uzunluğu' => '1 m',
                    'Emiş Derinliği' => '2 m',
                    'Giriş / Çıkış Ölçüsü' => '1/2 inç',
                    'Ölçüler (G × U × Y)' => '142 × 233 × 128 mm',
                ],
                <<<'HTML'
<p>Troy TS 24-15 H, 24 V DC karavan ve tekne hidroforudur. Etikette 168 W, 7 A, H max 55 m, Q max 900 L/h ve 3,2 kg yazar. 900 L/h, dakikada 15 litredir. Azami sıvı sıcaklığı 60 °C. Pompa yanıcı sıvılarda ve patlayıcı ortamlarda kullanılmaz.</p>
<p>Troy tablosunda emiş 2 m, kablo 1 m, giriş-çıkış 1/2 inç, ölçü 142 × 233 × 128 mm. 12 V karşılıkları <a href="/urun/troy-ts-12-8-h-12v-karavan-hidroforu">TS 12-8 H</a> ve <a href="/urun/troy-ts-12-12-h-12v-karavan-hidroforu">TS 12-12 H</a>. Üçü birlikte <a href="/kategoriler/hidrofor-sistemleri/karavan-hidroforu">karavan hidroforu</a> kategorisindedir. 12 V pompaya 24 V bağlanmaz.</p>
HTML,
            ),
        ];
    }

    /**
     * @param  array<string, string>  $specs
     * @return array<string, mixed>
     */
    private function product(
        string $slug,
        string $sku,
        string $name,
        string $short,
        string $metaDescription,
        float $price,
        float $compareAt,
        float $weight,
        float $width,
        float $height,
        float $depth,
        string $image,
        string $gallery,
        array $specs,
        string $description,
    ): array {
        return [
            'slug' => $slug,
            'sku' => $sku,
            'name' => $name,
            'short_description' => $short,
            'description' => $description,
            'price' => $price,
            'compare_at_price' => $compareAt,
            'image' => $image,
            'image_alt' => $name,
            'meta_title' => $name,
            'meta_description' => $metaDescription,
            'weight_kg' => $weight,
            'width_cm' => $width,
            'height_cm' => $height,
            'depth_cm' => $depth,
            'specs' => $specs,
            'gallery' => [
                'path' => $gallery,
                'alt' => $name.', ikinci görünüm',
            ],
        ];
    }

    private function categoryDescription(): string
    {
        return <<<'HTML'
<h2>12 ve 24 volt karavan hidroforu</h2>
<p>Karavan hidroforu, karavan veya teknedeki temiz su deposunu akü gerilimiyle musluğa basan pompadır. Bu sayfada üç Troy modeli var. Ev ve apartman için 220 V paketler <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisindedir; buradaki pompalar o tesisatın yerine geçmez.</p>
<h3>Etiket değerleri</h3>
<table>
<thead><tr><th>Model</th><th>Gerilim</th><th>Güç</th><th>Akım</th><th>Debi</th><th>Basma</th><th>Ağırlık</th></tr></thead>
<tbody>
<tr><td><a href="/urun/troy-ts-12-8-h-12v-karavan-hidroforu">TS 12-8 H</a></td><td>12 V DC</td><td>72 W</td><td>6 A</td><td>450 L/h (7,5 lt/dk)</td><td>45 m</td><td>2,6 kg</td></tr>
<tr><td><a href="/urun/troy-ts-12-12-h-12v-karavan-hidroforu">TS 12-12 H</a></td><td>12 V DC</td><td>90 W</td><td>7,5 A</td><td>700 L/h (11,7 lt/dk)</td><td>55 m</td><td>2,6 kg</td></tr>
<tr><td><a href="/urun/troy-ts-24-15-h-24v-karavan-hidroforu">TS 24-15 H</a></td><td>24 V DC</td><td>168 W</td><td>7 A</td><td>900 L/h (15 lt/dk)</td><td>55 m</td><td>3,2 kg</td></tr>
</tbody>
</table>
<p>Debi ve basma yüksekliği pompa etiketindeki Q max ve H max değerleridir. Litre/dakika, etiketteki L/h değerinin saat-dakika karşılığıdır. Azami sıvı sıcaklığı üç modelde de 60 °C. Troy tablosunda emiş 2 m, kablo 1 m, giriş-çıkış 1/2 inçtir. Marka sayfası: <a href="/marka/troy">Troy</a>.</p>
HTML;
    }

    private function buyingGuide(): string
    {
        return <<<'HTML'
<h3>Gerilimi aküyle eşleyin</h3>
<p>12 V pompaya 24 V, 24 V pompaya 12 V bağlanmaz. Etiket akımı TS 12-8 H için 6 A, TS 12-12 H için 7,5 A, TS 24-15 H için 7 A. Sigorta ve kablo kesiti bu akıma göre seçilir.</p>
<h3>Debiyi litre/saat okuyun</h3>
<p>TS 12-8 H 450 L/h, TS 12-12 H 700 L/h, TS 24-15 H 900 L/h basar. Basma yüksekliği 45 m veya 55 m. Bunlar etiket azamisidir; musluk yüksekliği ve hortum kaybı çalışma noktasını düşürür.</p>
<h3>Etiketteki sınır</h3>
<p>Azami sıvı sıcaklığı 60 °C. Pompa yanıcı sıvılarda ve patlayıcı ortamlarda kullanılmaz. Basınç tanklı ev hidroforu seçimi bu sayfanın konusu değildir.</p>
HTML;
    }

    /** @return list<array{q: string, a: string}> */
    private function categoryFaq(): array
    {
        return [
            [
                'q' => 'Karavan hidroforu ne işe yarar?',
                'a' => 'Karavan veya teknedeki temiz su deposunu 12 veya 24 V doğru akımla musluğa basar. Bu sayfadaki Troy modelleri 220 V ev hidroforu değildir. Ev tesisatı için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisine bakın.',
            ],
            [
                'q' => '12 volt hidrofor kaç amper çeker?',
                'a' => 'TS 12-8 H etiketinde 6 A, TS 12-12 H etiketinde 7,5 A yazar. 24 V model TS 24-15 H 7 A çeker.',
            ],
            [
                'q' => '12 V karavan hidroforu 24 V sistemde çalışır mı?',
                'a' => 'Çalışmaz. Pompa etiketindeki gerilim ile akü gerilimi aynı olmalıdır. 24 V sistem için TS 24-15 H kullanılır.',
            ],
            [
                'q' => 'Karavan hidroforu fiyatı neye göre değişir?',
                'a' => 'Bu rafta fiyat, 12 V ile 24 V ve debi sınıfına göre ayrılır. Güncel satış fiyatı ürün kartındadır. Ev tipi hidrofor fiyatları ayrı kategoridedir.',
            ],
        ];
    }
};
