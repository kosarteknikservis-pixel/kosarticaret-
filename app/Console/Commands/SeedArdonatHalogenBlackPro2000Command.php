<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageVariant;
use App\Support\ProductImageAlt;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ardonat Halogen Black Pro 2000W (5 kademeli kumandalı) ürününü SEO alanlarıyla oluşturur/günceller.
 */
final class SeedArdonatHalogenBlackPro2000Command extends Command
{
    protected $signature = 'catalog:seed-ardonat-halogen-black-pro-2000
                            {--price=7920 : Satis fiyati (TRY, KDV dahil referans). Bayi listesinde 6600+KDV.}
                            {--stock=10 : Stok adedi}
                            {--force : Mevcut urun SEO / gorsellerinin ustune yazar}';

    protected $description = 'Ardonat Halogen Black Pro 2000W 5 kademeli kumandali dis mekan isiticisini SEO icerik ve gorsellerle yukler.';

    private const SKU = 'HBP2000W';

    private const IMAGE_BASE = 'https://www.ardonatisicozumleri.com/tema/genel/uploads/';

    /** @var list<string> */
    private const IMAGE_FILES = [
        'halogen-black-pro-2000w-duvar-tipi-dis-mekan-isiticisi-kumandali-5-kademeli.jpg',
        'halogen-black-pro-2000w-duvar-tipi-dis-mekan-isiticisi-kumandali-5-kademeli-2.jpg',
        'halogen-black-pro-2000w-duvar-tipi-dis-mekan-isiticisi-kumandali-5-kademeli-3.jpg',
        'halogen-black-pro-2000w-duvar-tipi-dis-mekan-isiticisi-kumandali-5-kademeli-4.jpg',
    ];

    public function handle(): int
    {
        $brand = Brand::query()->where('slug', 'ardonat')->first();
        if ($brand === null) {
            $this->error('Ardonat markasi yok. Once: php artisan catalog:seed-ardonat-heating');

            return self::FAILURE;
        }

        $categoryIds = Category::query()
            ->whereIn('slug', [
                'duvar-tipi-dis-mekan-isiticilar',
                'dis-mekan-isiticilar',
                'elektrikli-isiticilar',
                'isitma-sistemleri',
            ])
            ->pluck('id')
            ->all();

        if ($categoryIds === []) {
            $this->error('Isitma kategorileri yok. Once: php artisan catalog:seed-ardonat-heating');

            return self::FAILURE;
        }

        $meta = $this->loadMetaJson();
        $name = $meta['name'] ?? 'Ardonat Halogen Black Pro 2000W 5 Kademeli Uzaktan Kumandalı Duvar Tipi Dış Mekân Isıtıcı';
        $slug = $meta['slug'] ?? 'ardonat-halogen-black-pro-2000w-5-kademeli-uzaktan-kumandali-duvar-tipi-dis-mekan-isitici';
        $price = max(0, (float) $this->option('price'));
        $stock = max(0, (int) $this->option('stock'));

        $product = Product::query()->where('sku', self::SKU)->orWhere('slug', $slug)->first();
        $isNew = $product === null;

        if ($isNew) {
            $slug = SlugHelper::assign('products', $slug, $name);
            $product = new Product(['sku' => self::SKU, 'slug' => $slug]);
        } elseif (! $this->option('force')) {
            $this->warn("Urun zaten var (#{$product->id}). Guncellemek icin --force kullanin.");

            return self::SUCCESS;
        }

        $imageAlt = $meta['image_alt'] ?? 'Ardonat Halogen Black Pro 2000W 5 kademeli uzaktan kumandalı duvar tipi dış mekân infrared ısıtıcı';
        $paths = $this->downloadImages();
        $cover = $paths[0] ?? $product->image;

        $product->fill([
            'sku' => self::SKU,
            'slug' => $product->slug ?: $slug,
            'name' => $name,
            'brand_id' => $brand->id,
            'price' => $price,
            'compare_at_price' => null,
            'stock' => $stock,
            'short_description' => $meta['short_description'] ?? null,
            'description' => RichContent::normalize($this->descriptionHtml()),
            'meta_title' => $meta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? null,
            'image' => $cover,
            'image_alt' => $imageAlt,
            'specs' => [
                'Marka' => 'Ardonat',
                'Model' => 'Halogen Black Pro 2000W',
                'Güç' => '2000 W',
                'Voltaj' => '220/230 V',
                'Ölçüler' => '54 × 12,5 × 8,5 cm',
                'Renk' => 'Siyah (statik boya)',
                'Gövde' => 'Alüminyum',
                'Montaj' => 'Duvar / tavan, yatay',
                'Kontrol' => '5 kademeli uzaktan kumanda',
                'Isıtma teknolojisi' => 'Orta dalga infrared (halojen)',
                'Kullanım alanı' => 'Dış mekân, yarı açık alan',
                'Üretici kodu' => (string) ($meta['manufacturer_sku'] ?? 'blackpro2000w'),
                'SKU' => self::SKU,
            ],
            'tags' => ['ardonat', 'halogen-black-pro', 'dis-mekan-isitici', 'infrared', 'duvar-tipi', '2000w', 'kumandali'],
            'width_cm' => 54,
            'height_cm' => 12.5,
            'depth_cm' => 8.5,
            'vat_rate' => 20,
            'featured' => true,
            'is_active' => true,
            'marketplace_enabled' => true,
            'rating' => 0,
            'review_count' => 0,
        ]);

        $product->save();
        $product->categories()->sync($categoryIds);

        if ($paths !== []) {
            $product->images()->delete();
            foreach ($paths as $i => $path) {
                if ($i === 0) {
                    continue;
                }
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'alt' => ProductImageAlt::generate($name, $brand->name).' — görsel '.($i + 1),
                    'sort_order' => $i,
                ]);
            }
        }

        $this->info(($isNew ? 'Olusturuldu' : 'Guncellendi').": {$product->slug} (#{$product->id})");
        $this->line('URL: '.route('products.show', $product));
        $this->line("Fiyat: {$price} TL | Stok: {$stock}");
        if ($cover === null) {
            $this->warn('Kapak gorseli indirilemedi; panilden yukleyin.');
        }

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function loadMetaJson(): array
    {
        $path = database_path('data/ardonat_halogen_black_pro_2000.json');
        if (! is_file($path)) {
            return [];
        }
        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /** @return list<string> relative public-disk paths */
    private function downloadImages(): array
    {
        $saved = [];
        foreach (self::IMAGE_FILES as $i => $file) {
            $url = self::IMAGE_BASE.$file;
            $skuKey = self::SKU.($i === 0 ? '' : '-g'.$i);
            $path = $this->downloadOne($url, $skuKey, $i === 0 ? 'product' : 'product-gallery');
            if ($path !== null) {
                $saved[] = $path;
            }
        }

        return $saved;
    }

    private function downloadOne(string $url, string $skuKey, string $preset): ?string
    {
        try {
            $response = Http::timeout(45)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; KosarBot/1.0)'])
                ->retry(2, 500)
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 1000) {
                $this->warn("Gorsel indirilemedi: {$url}");

                return null;
            }

            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }

            $path = 'products/ardonat/'.Str::slug($skuKey).'.'.$ext;
            Storage::disk('public')->put($path, $response->body());
            ImageVariant::generate($path, ImageVariant::presetsFor($preset));
            $this->line("Gorsel: {$path}");

            return $path;
        } catch (\Throwable $e) {
            $this->warn('Gorsel hatasi: '.$e->getMessage());

            return null;
        }
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Ardonat Halogen Black Pro 2000W — 5 Kademeli Uzaktan Kumandalı Duvar Tipi Isıtıcı</h2>
<p><strong>Ardonat Halogen Black Pro 2000W</strong>, kafe terası, restoran verandası ve yarı açık oturma alanlarında lokal ısı için tasarlanmış duvar tipi infrared (halojen) ısıtıcıdır. Standart Halogen Black 2000W’den farkı <strong>5 kademeli uzaktan kumanda</strong>dır: güç ihtiyaca göre düşürülerek hem konfor hem elektrik tüketimi kontrol edilir. Orta dalga kızılötesi teknoloji önce yüzey ve kişileri ısıtır; açık alanda fanlı ısıtıcılara göre daha hedefli ısınma sağlar.</p>

<h3>Teknik Özellikler</h3>
<table>
<thead><tr><th>Özellik</th><th>Değer</th></tr></thead>
<tbody>
<tr><td>Güç</td><td>2000 W (5 kademeli)</td></tr>
<tr><td>Besleme</td><td>220/230 V</td></tr>
<tr><td>Ölçüler</td><td>54 × 12,5 × 8,5 cm</td></tr>
<tr><td>Gövde</td><td>Alüminyum, siyah statik boya</td></tr>
<tr><td>Montaj</td><td>Yatay — duvar veya tavan</td></tr>
<tr><td>Kontrol</td><td>5 kademeli uzaktan kumanda</td></tr>
<tr><td>Kullanım</td><td>Dış mekân ve yarı açık alan</td></tr>
<tr><td>Üretici kodu</td><td>blackpro2000w</td></tr>
</tbody>
</table>

<h3>Kimler İçin Uygun?</h3>
<ul>
  <li>Kafe ve restoran teraslarında gün içinde güç kademesi değiştirmek isteyen işletmeler</li>
  <li>Uzaktan kumanda ile pratik kullanım arayan balkon, veranda ve bahçe oturma grupları</li>
  <li>Sabit 2000 W yerine kademeli tüketim kontrolü isteyen kullanıcılar</li>
  <li>Duvar tipi yatay montajla alan tasarrufu arayan yarı açık mekânlar</li>
</ul>
<p>Kumandasız / daha ekonomik seçenek için <a href="/urun/ardonat-halogen-black-2000w-duvar-tipi-dis-mekan-isitici-kumandasiz">Halogen Black 2000W (kumandasız)</a> modeline bakın. Geniş alanlarda Twin veya 3000 W serileri <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar/duvar-tipi-dis-mekan-isiticilar">duvar tipi dış mekân ısıtıcılar</a> ve <a href="/marka/ardonat">Ardonat marka sayfasında</a> yer alır.</p>

<h3>Pro vs Standart Black 2000W</h3>
<p>Standart model prize tak-çıkar aç-kapa kontroldür. <strong>Pro</strong> modelde 5 kademeli uzaktan kumanda vardır; kısmi doluluk veya ılık akşamlarda düşük kademe ile çalıştırılabilir. Ölçü bandı Pro’da 54 cm uzunluktadır (standart 2000W modeli daha kompakt 47 cm gövdeye sahiptir).</p>

<h3>Kurulum ve Güvenlik Notları</h3>
<p>Yatay montajda üreticinin önerdiği yükseklik ve yanıcı malzemeye uzaklık korunmalıdır. Mümkünse saçak altı veya yarı açık konum tercih edilir; elektrik bağlantısı uygun kesitte ve yetkili elektrikçi kontrolünde yapılmalıdır. Cihaz dış mekân / yarı açık kullanımına yöneliktir; sürekli kapalı oda ısıtması için ev tipi veya panel seriler daha uygundur.</p>

<p>Koşar Ticaret’te orijinal Ardonat ürünleri teknik özellikleriyle listelenir. Stok ve teslimat için ürün kartındaki fiyat ile stok bilgisini kontrol edin; alan ölçünüzü paylaşırsanız <a href="/iletisim">teknik destek</a> üzerinden model önerisi alabilirsiniz.</p>
HTML;
    }
}
