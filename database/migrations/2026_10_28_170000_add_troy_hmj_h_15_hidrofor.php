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
    private const SLUG = 'troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli';

    public function up(): void
    {
        if (Product::query()->where('slug', self::SLUG)->exists()) {
            return;
        }

        $category = Category::query()->where('slug', 'hidroforlar')->first();
        if ($category === null) {
            if (app()->runningUnitTests()) {
                return;
            }

            throw new \RuntimeException('hidroforlar kategorisi yok.');
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

        // Marka sayfası artık yalnızca karavan değil; ev tipi paket de listelenir.
        $brand->fill([
            'description' => $seo['description'],
            'meta_title' => $seo['meta_title'],
            'meta_description' => $seo['meta_description'],
            'faq' => $seo['faq'],
        ])->save();

        $imagePath = $this->publishImage();

        Product::withoutEvents(function () use ($brand, $category, $imagePath): void {
            $name = 'Troy HMJ H 15 Hidrofor 4 Kat 4 Daire 24 Litre Küre Tanklı';

            $product = Product::query()->create([
                'slug' => self::SLUG,
                'sku' => 'HMJH-15',
                'name' => $name,
                'short_description' => 'Troy HMJ H 15 paket hidrofor: 1 HP (0,75 kW), 220 V, 24 litre küre tank. 4 kat / 4 daire konut basınçlandırması için.',
                'description' => $this->descriptionHtml(),
                'brand_id' => $brand->id,
                'price' => 10990.00,
                'compare_at_price' => 13737.50,
                'stock' => 1,
                'rating' => 0,
                'review_count' => 0,
                'featured' => false,
                'is_active' => true,
                'marketplace_enabled' => false,
                'image' => $imagePath,
                'image_alt' => $name,
                'meta_title' => $name,
                'meta_description' => 'Troy HMJ H 15 paket hidrofor: 1 HP, 220 V, 24 litre küre tank, 4 kat 4 daire. Jet pompalı ev tipi hidrofor; teknik tablo ve seçim notu.',
                'weight_kg' => 18,
                'width_cm' => 35.0,
                'height_cm' => 59.0,
                'depth_cm' => 45.0,
                'vat_rate' => config('marketplace.default_vat_rate', 20),
                'specs' => [
                    'Marka' => 'Troy',
                    'Model' => 'HMJ H 15',
                    'Tip' => 'Jet pompalı paket hidrofor',
                    'Motor Gücü (HP)' => '1 HP',
                    'Motor Gücü (kW)' => '0,75 kW (750 W)',
                    'Voltaj' => '220 V monofaze',
                    'Maks. Debi' => '3,5 m³/h',
                    'Maks. Basma Yüksekliği' => '52 mss',
                    'Tank' => '24 litre küre tip genleşme tankı',
                    'Kat / Daire' => '4 kat / 4 daire',
                    'Giriş / Çıkış' => '1"',
                    'Paket içeriği' => 'Pompa, küre tank, 5 yollu, basınç şalteri, manometre',
                    'Ölçüler (G × U × Y)' => '350 × 450 × 590 mm',
                    'Ağırlık' => '18 kg',
                ],
            ]);

            $product->categories()->attach($category->id);

            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $imagePath,
                'alt' => $name,
                'sort_order' => 1,
            ]);
        });

        ImageVariant::generate($imagePath, array_values(array_unique([
            ...ImageVariant::presetsFor('product'),
            ...ImageVariant::presetsFor('product-gallery'),
        ])));
    }

    public function down(): void
    {
        Product::withoutEvents(function (): void {
            Product::query()->where('slug', self::SLUG)->each(function (Product $product): void {
                $product->images()->delete();
                $product->categories()->detach();
                $product->delete();
            });
        });
    }

    private function publishImage(): string
    {
        $source = database_path('seed-assets/troy/hmj-15-1.jpg');
        if (! is_file($source)) {
            throw new \RuntimeException('Troy HMJ H 15 görseli yok: '.$source);
        }

        $target = 'products/troy/hmj-15-1.jpg';
        Storage::disk('public')->put($target, (string) file_get_contents($source));

        return $target;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Troy HMJ H 15 paket hidrofor</h2>
<p><strong>Troy HMJ H 15</strong>, 1 HP (0,75 kW) jet pompa ile 24 litre küre tip genleşme tankının birleştiği monofaze paket hidrofordur. Bu sayfadaki ürün 4 kat / 4 daire konut basınçlandırması için listelenir. Şebeke basıncının yetersiz kaldığı müstakil ev ve küçük apartmanlarda; depodan temiz su basma uygulamalarında kullanılır.</p>
<p>Üretici tablosunda aynı serinin HMJ H 15 C satırında 750 W, 220 V, azami debi 3,5 m³/h ve azami basma yüksekliği 52 mss yazar. Tank hacmi 24 litredir. Ölçü 350 × 450 × 590 mm, ağırlık yaklaşık 18 kg. Pakette basınç şalteri, manometre ve 5 yollu bağlantı bulunur; musluk açılınca pompa otomatik devreye girer.</p>
<h3>Ne zaman seçilir?</h3>
<ul>
<li>4 kata / 4 daireye kadar ev tipi basınç ihtiyacı</li>
<li>220 V monofaze hat</li>
<li>24 litre küre tanklı, hazır paket sistem</li>
</ul>
<p>Daha yüksek debi veya kat için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisindeki diğer paketlere bakın. Karavan / tekne 12–24 V modelleri ayrıdır: <a href="/kategoriler/hidrofor-sistemleri/karavan-hidroforu">karavan hidroforu</a>. Marka özeti: <a href="/marka/troy">Troy</a>.</p>
<p>İlk çalışmada pompa gövdesine su doldurulmalıdır. Depo emişinde çekvalf veya dip klapesi ve seviye flatörü önerilir. Sıcak su hattı için uygun değildir. Genleşme tankı ön gaz basıncı periyodik kontrol edilmelidir.</p>
HTML;
    }
};
