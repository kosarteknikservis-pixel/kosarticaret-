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
 * Ardonat Halogen Black 2000W (kumandasız) ürününü SEO alanlarıyla oluşturur/günceller.
 */
final class SeedArdonatHalogenBlack2000Command extends Command
{
    protected $signature = 'catalog:seed-ardonat-halogen-black-2000
                            {--price=3960 : Satis fiyati (TRY). Uretici sitesinde 0; referans bayi fiyati varsayilan.}
                            {--stock=10 : Stok adedi}
                            {--force : Mevcut urun SEO / gorsellerinin ustune yazar}';

    protected $description = 'Ardonat Halogen Black 2000W kumandasiz dis mekan isiticisini SEO icerik ve gorsellerle yukler.';

    private const SKU = 'HB2000W';

    private const IMAGE_BASE = 'https://www.ardonatisicozumleri.com/tema/genel/uploads/';

    /** @var list<string> */
    private const IMAGE_FILES = [
        'halogen-black-2000w-duvar-tipi-dis-mekan-isiticisi-kumandasizz.jpg',
        'halogen-black-2000w-duvar-tipi-dis-mekan-isiticisi-kumandasiz-2.jpg',
        'halogen-black-2000w-duvar-tipi-dis-mekan-isiticisi-kumandasizz-3.jpg',
        'halogen-black-2000w-duvar-tipi-dis-mekan-isiticisi-kumandasizz-4.jpg',
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
        $name = $meta['name'] ?? 'Ardonat Halogen Black 2000W Duvar Tipi Dış Mekân Isıtıcı (Kumandasız)';
        $slug = $meta['slug'] ?? 'ardonat-halogen-black-2000w-duvar-tipi-dis-mekan-isitici-kumandasiz';
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

        $imageAlt = $meta['image_alt'] ?? 'Ardonat Halogen Black 2000W duvar tipi dış mekân infrared ısıtıcı ürün görseli';
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
                'Model' => 'Halogen Black 2000W',
                'Güç' => '2000 W',
                'Voltaj' => '220/230 V',
                'Ölçüler' => '47 × 12,5 × 8,5 cm',
                'Renk' => 'Siyah (statik boya)',
                'Gövde' => 'Alüminyum',
                'Montaj' => 'Duvar / tavan, yatay',
                'Kontrol' => 'Kumandasız (tak-çıkar priz)',
                'Isıtma teknolojisi' => 'Orta dalga infrared (halojen)',
                'Kullanım alanı' => 'Dış mekân, yarı açık alan',
                'SKU' => self::SKU,
            ],
            'tags' => ['ardonat', 'halogen-black', 'dis-mekan-isitici', 'infrared', 'duvar-tipi', '2000w'],
            'width_cm' => 47,
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
        $path = database_path('data/ardonat_halogen_black_2000.json');
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
<h2>Ardonat Halogen Black 2000W — Kumandasız Duvar Tipi Dış Mekân Isıtıcı</h2>
<p><strong>Ardonat Halogen Black 2000W</strong>, kafe terası, restoran verandası, bahçe ve yarı açık oturma alanlarında lokal ısı sağlamak için tasarlanmış duvar tipi infrared (halojen) ısıtıcıdır. Bu model <strong>kumandasız</strong>dır; cihaz prize takılarak açılır-kapanır. Orta dalga kızılötesi teknoloji önce yüzey ve kişileri ısıtır; açık alanda rüzgâra karşı klasik fanlı ısıtıcılara göre daha hedefli konfor sunar.</p>

<h3>Teknik Özellikler</h3>
<table>
<thead><tr><th>Özellik</th><th>Değer</th></tr></thead>
<tbody>
<tr><td>Güç</td><td>2000 W</td></tr>
<tr><td>Besleme</td><td>220/230 V</td></tr>
<tr><td>Ölçüler</td><td>47 × 12,5 × 8,5 cm</td></tr>
<tr><td>Gövde</td><td>Alüminyum, siyah statik boya</td></tr>
<tr><td>Montaj</td><td>Yatay — duvar veya tavan</td></tr>
<tr><td>Kontrol</td><td>Tak-çıkar priz (kademe / kumanda yok)</td></tr>
<tr><td>Kullanım</td><td>Dış mekân ve yarı açık alan</td></tr>
</tbody>
</table>

<h3>Kimler İçin Uygun?</h3>
<ul>
  <li>Sabit duvar montajı isteyen kafe ve küçük restoran terasları</li>
  <li>Balkon, veranda ve bahçe oturma gruplarında lokal ısı ihtiyacı</li>
  <li>Uzaktan kumanda veya güç kademesi istemeyen, sade aç-kapa kullanım</li>
  <li>Standart prize bağlanabilen 2000 W tek lamba çözümler</li>
</ul>
<p>Daha geniş alan veya kumandalı kademe gerekiyorsa <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar/duvar-tipi-dis-mekan-isiticilar">duvar tipi dış mekân ısıtıcılar</a> içindeki Pro / Twin modelleri veya <a href="/marka/ardonat">Ardonat marka sayfasını</a> inceleyin.</p>

<h3>Kurulum ve Güvenlik Notları</h3>
<p>Yatay montajda üreticinin önerdiği yükseklik ve yanıcı malzemeye uzaklık korunmalıdır. Mümkünse saçak altı veya yarı açık konum tercih edilir; elektrik bağlantısı uygun kesitte ve yetkili elektrikçi kontrolünde yapılmalıdır. Cihaz dış mekân kullanımına yöneliktir; iç mekân sürekli oda ısıtması için panel veya ev tipi seriler daha uygundur.</p>

<h3>Halogen Black 2000W ile Pro Farkı</h3>
<p>Bu ürün <strong>aç-kapa (kumandasız)</strong> kontroldür. Aynı güç bandındaki <strong>Halogen Black Pro 2000W</strong> modellerinde genelde 5 kademeli uzaktan kumanda bulunur. Bütçe ve kullanım sıklığına göre sade model veya Pro tercih edilir.</p>

<p>Koşar Ticaret’te orijinal Ardonat ürünleri teknik özellikleriyle listelenir. Stok ve teslimat için ürün kartındaki fiyat ile stok bilgisini kontrol edin; uygulama ölçünüzü paylaşırsanız <a href="/iletisim">teknik destek</a> üzerinden model önerisi alabilirsiniz.</p>
HTML;
    }
}
