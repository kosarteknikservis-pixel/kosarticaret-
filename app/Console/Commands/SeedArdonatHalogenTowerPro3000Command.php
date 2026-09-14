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
 * Ardonat Halogen Tower Pro 3000W (5 kademeli kumandalı dikey) ürününü SEO alanlarıyla oluşturur/günceller.
 */
final class SeedArdonatHalogenTowerPro3000Command extends Command
{
    protected $signature = 'catalog:seed-ardonat-halogen-tower-pro-3000
                            {--price=9900 : Satis fiyati (TRY, KDV dahil gecici referans; panilden guncelleyin)}
                            {--stock=10 : Stok adedi}
                            {--force : Mevcut urun SEO / gorsellerinin ustune yazar}';

    protected $description = 'Ardonat Halogen Tower Pro 3000W 5 kademeli kumandali dikey dis mekan isiticisini SEO icerik ve gorsellerle yukler.';

    private const SKU = 'HTP3000WKU';

    /**
     * Görseller seed sırasında indirilir; vitrine kaynak site linki yazılmaz.
     *
     * @var list<string>
     */
    private const IMAGE_URLS = [
        'https://www.cantekstore.com/uploads/urunler/halogen-tower-pro-3000w-5-kademeli-uzaktan-kumandali-3hvbx.jpg',
        'https://www.cantekstore.com/uploads/urunler/halogen-tower-pro-3000w-5-kademeli-uzaktan-kumandali-hy1a9.webp',
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
                'dikey-tower-dis-mekan-isiticilar',
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
        $name = $meta['name'] ?? 'Ardonat Halogen Tower Pro 3000W 5 Kademeli Uzaktan Kumandalı Dikey Dış Mekân Isıtıcı';
        $slug = $meta['slug'] ?? 'ardonat-halogen-tower-pro-3000w-5-kademeli-uzaktan-kumandali-dikey-dis-mekan-isitici';
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

        $imageAlt = $meta['image_alt'] ?? 'Ardonat Halogen Tower Pro 3000W 5 kademeli uzaktan kumandalı dikey dış mekân infrared ısıtıcı';
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
                'Model' => 'Halogen Tower Pro 3000W',
                'Güç' => '3000 W',
                'Voltaj' => '220/230 V',
                'Form' => 'Dikey tower (ayaklı)',
                'Renk' => 'Siyah',
                'Montaj' => 'Ayaklı / dikey konumlandırma',
                'Kontrol' => '5 kademeli uzaktan kumanda',
                'Isıtma teknolojisi' => 'Orta dalga infrared (halojen)',
                'Kullanım alanı' => 'Dış mekân, açık ve yarı açık alan',
                'Üretim yeri' => 'Türkiye',
                'SKU' => self::SKU,
            ],
            'tags' => ['ardonat', 'halogen-tower-pro', 'dis-mekan-isitici', 'infrared', 'dikey-tower', '3000w', 'kumandali', 'ayakli'],
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
        $path = database_path('data/ardonat_halogen_tower_pro_3000.json');
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
        foreach (self::IMAGE_URLS as $i => $url) {
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
<h2>Ardonat Halogen Tower Pro 3000W — 5 Kademeli Uzaktan Kumandalı Dikey Isıtıcı</h2>
<p><strong>Ardonat Halogen Tower Pro 3000W</strong>, veranda, bahçe, kafe terası ve yarı açık oturma alanlarında lokal ısı için tasarlanmış <strong>dikey tower (ayaklı)</strong> infrared ısıtıcıdır. Duvar montajı gerektirmeden konumlandırılır; 3000 W güç ve <strong>5 kademeli uzaktan kumanda</strong> ile alanın doluluk ve hava koşullarına göre güç ayarı yapılabilir. Orta dalga kızılötesi teknoloji önce yüzey ve kişileri ısıtır; açık alanda klasik fanlı cihazlara göre daha hedefli konfor sunar.</p>

<h3>Teknik Özellikler</h3>
<table>
<thead><tr><th>Özellik</th><th>Değer</th></tr></thead>
<tbody>
<tr><td>Güç</td><td>3000 W (5 kademeli)</td></tr>
<tr><td>Besleme</td><td>220/230 V</td></tr>
<tr><td>Form</td><td>Dikey tower / ayaklı</td></tr>
<tr><td>Kontrol</td><td>5 kademeli uzaktan kumanda</td></tr>
<tr><td>Isıtma</td><td>Infrared (halojen)</td></tr>
<tr><td>Kullanım</td><td>Açık ve yarı açık dış mekân</td></tr>
<tr><td>Üretim</td><td>Türkiye</td></tr>
<tr><td>Stok kodu</td><td>HTP3000WKU</td></tr>
</tbody>
</table>

<h3>Kimler İçin Uygun?</h3>
<ul>
  <li>Duvar veya tavan montajı istemeyen veranda ve bahçe kullanıcıları</li>
  <li>Kafe / restoran teraslarında esnek konumlandırma ihtiyacı</li>
  <li>3000 W ile daha geniş lokal ısı alanı ve kademeli güç kontrolü arayanlar</li>
  <li>Yarı açık balkon ve oturma gruplarında dikey form tercih edenler</li>
</ul>
<p>Sabit duvar montajı için <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar/duvar-tipi-dis-mekan-isiticilar">duvar tipi dış mekân ısıtıcılar</a> (ör. <a href="/urun/ardonat-halogen-black-pro-2000w-5-kademeli-uzaktan-kumandali-duvar-tipi-dis-mekan-isitici">Halogen Black Pro 2000W</a>) daha uygundur. Tower serisinin diğer seçenekleri <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar/dikey-tower-dis-mekan-isiticilar">dikey tower dış mekân ısıtıcılar</a> ve <a href="/marka/ardonat">Ardonat marka sayfasında</a> yer alır.</p>

<h3>Tower Pro vs Duvar Tipi Black</h3>
<p>Duvar tipi Halogen Black modeller yatay sabit montajla alan tasarrufu sağlar. <strong>Tower Pro</strong> dikey / ayaklıdır; mobilya düzenine göre taşınabilir veya yeniden konumlandırılabilir. 3000 W kademeli kontrol, kısmi doluluk veya ılık akşamlarda tüketimi düşürmeye yardımcı olur.</p>

<h3>Kurulum ve Güvenlik Notları</h3>
<p>Cihaz düz ve dengeli zemine yerleştirilmeli; üreticinin önerdiği yanıcı malzemeye uzaklık ve devrilme riski dikkate alınmalıdır. Mümkünse saçak altı veya yarı açık konum tercih edilir. Elektrik bağlantısı uygun kesitte ve yetkili elektrikçi kontrolünde yapılmalıdır. Sürekli kapalı oda ısıtması için ev tipi veya panel seriler daha uygundur.</p>

<p>Koşar Ticaret’te orijinal Ardonat ürünleri teknik özellikleriyle listelenir. Stok ve teslimat için ürün kartındaki fiyat ile stok bilgisini kontrol edin; alan ölçünüzü paylaşırsanız <a href="/iletisim">teknik destek</a> üzerinden model önerisi alabilirsiniz.</p>
HTML;
    }
}
