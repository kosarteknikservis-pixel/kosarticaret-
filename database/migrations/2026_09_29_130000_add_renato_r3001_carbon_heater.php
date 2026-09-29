<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageVariant;
use App\Support\RichContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const SKU = 'R-3001';

    private const SLUG = 'renato-r-3001-2500w-kumandali-dikey-karbon-isitici-6-kademe-termostatli';

    private const NAME = 'Renato R-3001 2500W Kumandalı Dikey Karbon Isıtıcı, 6 Kademe, Termostatlı';

    private const SOURCE_DIR = 'data/product-images/renato-r-3001';

    private const STORAGE_DIR = 'products/renato';

    /**
     * İlk görsel kapak; kalanlar galeri sırasıdır.
     *
     * @var array<string, string>
     */
    private array $images = [
        '01-renato-r-3001-2500w-dikey-karbon-isitici.jpg' => 'Renato R-3001 2500W kumandalı dikey karbon ısıtıcı, beyaz fonda açılı görünüm',
        '02-renato-r-3001-karbon-isitici-on-gorunum.jpg' => 'Renato R-3001 karbon ısıtıcının önden görünümü, dijital ekranlı gövde ve ayak',
        '03-renato-r-3001-dikey-isitici-salon-kullanimi.jpg' => 'Renato R-3001 dikey karbon ısıtıcının salonda koltuk yanında kullanımı',
        '04-renato-r-3001-uzaktan-kumandali-isitici.jpg' => 'Renato R-3001 2500W ısıtıcının uzaktan kumandayla kullanımı ve özellikleri',
        '05-renato-r-3001-isitici-ozellikleri.jpg' => 'Renato R-3001 ısıtıcı özellikleri: termostat, 6 kademe, yatay ve dikey kullanım',
        '06-renato-r-3001-ayarlanabilir-aci-kullanim-alanlari.jpg' => 'Renato R-3001 ısıtıcının ayarlanabilir açısı ile ev, kafe ve ofis kullanımı',
        '07-renato-r-3001-duvar-ve-ayakli-kullanim.jpg' => 'Renato R-3001 ısıtıcının duvara yatay ve ayaklı dikey kullanım örnekleri',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('brands')) {
            return;
        }

        if (Product::query()->where('sku', self::SKU)->orWhere('slug', self::SLUG)->exists()) {
            return;
        }

        $brand = Brand::query()->firstOrCreate(
            ['slug' => 'renato'],
            [
                'name' => 'Renato',
                'active' => true,
                'featured' => false,
                'sort_order' => 20,
                'meta_title' => 'Renato Isıtıcı Modelleri ve Fiyatları',
                'meta_description' => 'Renato elektrikli ısıtıcı modelleri: 2500W, uzaktan kumandalı ve termostatlı karbon ısıtıcılar. Orijinal ürün, 2 yıl garanti ve teknik destek.',
                'description' => $this->brandDescriptionHtml(),
                'faq' => $this->brandFaq(),
            ]
        );

        $paths = $this->copyImages();
        $alts = array_values($this->images);

        $product = Product::query()->create([
            'sku' => self::SKU,
            'slug' => self::SLUG,
            'name' => self::NAME,
            'brand_id' => $brand->id,
            'price' => 8999,
            'compare_at_price' => null,
            'stock' => 100,
            'short_description' => 'Renato R-3001 dikey karbon ısıtıcı: 2500W güç, 6 kademe, termostat, uzaktan kumanda ve dijital ekran. Ayaklı ya da duvara yatay kullanılır.',
            'description' => RichContent::normalize($this->descriptionHtml()),
            'meta_title' => 'Renato R-3001 Kumandalı Dikey Karbon Isıtıcı',
            'meta_description' => 'Renato R-3001 2500W karbon infrared ısıtıcı: 6 kademeli ısı programı, termostat, uzaktan kumanda, yatay ve dikey kullanım. Ev, ofis ve kafe için.',
            'image' => $paths[0] ?? null,
            'image_alt' => $alts[0],
            'specs' => [
                'Model' => 'R-3001',
                'Isıtma gücü' => '2500 W',
                'Isıtma elemanı' => 'Karbon rezistans (infrared)',
                'Isı ayarı' => '6 kademeli ısı programı',
                'Termostat' => 'Var',
                'Kontrol' => 'Uzaktan kumanda, dijital ekran',
                'Marka' => 'Renato',
                'Kullanım' => 'Ayaklı dikey veya duvara yatay',
                'Montaj aparatı' => 'Duvar askı aparatı kutu içeriğinde',
                'Ayar' => 'Yükseklik ve açı ayarlanabilir',
                'Fan' => 'Yok (sessiz çalışma)',
                'Renk' => 'Siyah gövde, gümüş detay',
                'Kullanım alanı' => 'Ev, ofis, kafe ve mağaza iç mekânları',
                'Garanti' => '2 yıl',
            ],
            'tags' => ['renato', 'karbon-isitici', 'dikey-isitici', 'kumandali-isitici', 'termostatli-isitici', '2500w'],
            'vat_rate' => 20,
            'featured' => true,
            'is_active' => true,
            'marketplace_enabled' => true,
            'rating' => 0,
            'review_count' => 0,
        ]);

        $categoryIds = Category::query()
            ->whereIn('slug', [
                'dikey-ev-tipi-isiticilar',
                'ev-tipi-isiticilar',
                'elektrikli-isiticilar',
                'isitma-sistemleri',
            ])
            ->pluck('id')
            ->all();

        if ($categoryIds !== []) {
            $product->categories()->sync($categoryIds);
        }

        foreach (array_slice($paths, 1, null, true) as $i => $path) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $alts[$i] ?? self::NAME,
                'sort_order' => $i,
            ]);
        }
    }

    public function down(): void
    {
        $product = Product::query()->where('sku', self::SKU)->first();
        if ($product === null) {
            return;
        }

        $paths = array_filter([$product->image, ...$product->images()->pluck('path')->all()]);
        foreach ($paths as $path) {
            ImageVariant::delete($path);
            Storage::disk('public')->delete($path);
        }

        $product->images()->delete();
        $product->categories()->detach();
        $brandId = $product->brand_id;
        $product->delete();

        if ($brandId !== null && ! Product::query()->where('brand_id', $brandId)->exists()) {
            Brand::query()->whereKey($brandId)->where('slug', 'renato')->delete();
        }
    }

    /** @return list<string> public disk yolları, $images sırasıyla */
    private function copyImages(): array
    {
        $saved = [];
        foreach (array_keys($this->images) as $i => $file) {
            $source = database_path(self::SOURCE_DIR.'/'.$file);
            if (! is_file($source)) {
                continue;
            }

            $path = self::STORAGE_DIR.'/'.$file;
            Storage::disk('public')->put($path, (string) file_get_contents($source));
            ImageVariant::generate($path, ImageVariant::presetsFor($i === 0 ? 'product' : 'product-gallery'));
            $saved[$i] = $path;
        }

        return $saved;
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Renato R-3001 Dikey Karbon Isıtıcı: 2500W Güç, Uzaktan Kumanda ve Termostat</h2>
<p><strong>Renato R-3001</strong>, salon, çalışma odası, ofis ve kafe gibi iç mekânlarda hızlı ve lokal ısınma için tasarlanmış <strong>2500W karbon rezistanslı infrared ısıtıcıdır</strong>. Ayaklı olarak dikey konumda kullanılabildiği gibi kutudan çıkan askı aparatıyla duvara yatay olarak da monte edilebilir. <strong>6 kademeli ısı programı</strong>, <strong>termostat</strong> ve <strong>uzaktan kumanda</strong> sayesinde ısıyı oturduğunuz yerden ihtiyacınıza göre ayarlarsınız; seçili kademe gövdedeki dijital ekranda görünür.</p>

<h3>Öne Çıkan Özellikler</h3>
<ul>
  <li><strong>2500W yüksek güç:</strong> Açıldıktan kısa süre sonra baktığı alanı ısıtmaya başlar.</li>
  <li><strong>6 kademeli ısı programı:</strong> Serin günlerde düşük, soğuk günlerde yüksek kademe seçilir.</li>
  <li><strong>Termostat:</strong> Ayarlanan sıcaklığa göre ısıtmayı düzenleyerek dengeli bir ortam sağlar.</li>
  <li><strong>Uzaktan kumanda ve dijital ekran:</strong> Açma-kapama ve kademe ayarı koltuktan yapılır.</li>
  <li><strong>Karbon rezistans:</strong> Isıyı kızılötesi ışınımla doğrudan kişilere ve yüzeylere iletir.</li>
  <li><strong>Yatay ve dikey kullanım:</strong> Ayaklı dikey konum veya duvara yatay montaj; yükseklik ve açı ayarlanabilir.</li>
  <li><strong>Fansız, sessiz çalışma:</strong> Pervane olmadığı için uğultu yapmaz, havayı ve tozu savurmaz.</li>
</ul>

<h3>Teknik Özellikler</h3>
<table>
<thead><tr><th>Özellik</th><th>Değer</th></tr></thead>
<tbody>
<tr><td>Marka / Model</td><td>Renato R-3001</td></tr>
<tr><td>Isıtma gücü</td><td>2500 W</td></tr>
<tr><td>Isıtma elemanı</td><td>Karbon rezistans (infrared)</td></tr>
<tr><td>Isı ayarı</td><td>6 kademeli ısı programı</td></tr>
<tr><td>Termostat</td><td>Var</td></tr>
<tr><td>Kontrol</td><td>Uzaktan kumanda, dijital ekran</td></tr>
<tr><td>Kullanım şekli</td><td>Ayaklı dikey veya duvara yatay montaj</td></tr>
<tr><td>Montaj aparatı</td><td>Duvar askı aparatı kutu içeriğinde</td></tr>
<tr><td>Ayar</td><td>Yükseklik ve açı ayarlanabilir</td></tr>
<tr><td>Fan</td><td>Yok (sessiz çalışma)</td></tr>
<tr><td>Renk</td><td>Siyah gövde, gümüş detay</td></tr>
<tr><td>Garanti</td><td>2 yıl</td></tr>
</tbody>
</table>

<h3>Karbon Isıtıcı Nasıl Çalışır?</h3>
<p>Karbon ısıtıcılar havayı üfleyerek değil, <strong>kızılötesi (infrared) ışınımla</strong> ısıtır. Karbon rezistansın yaydığı ısı önündeki kişilere, mobilyalara ve zemine ulaşır; bu yüzden cihazın baktığı yönde sıcaklık kısa sürede hissedilir. Fanlı ısıtıcılardaki gibi hava akımı oluşmadığı için ses yapmaz ve ortamdaki tozu dolaştırmaz. Elektrikle çalıştığı için yanma olmaz; ortamda gaz, duman ya da koku oluşmaz.</p>
<p>Infrared ısı en çok cihazın yöneldiği alanda etkilidir. Geniş bir odanın tamamını eşit ısıtmaktan çok koltuk, masa veya çalışma alanı gibi kullanılan bölgeyi ısıtmaya uygundur. En iyi sonuç için R-3001’i oturma alanınıza dönük konumlandırın.</p>

<h3>Hangi Alanlarda Kullanılır?</h3>
<ul>
  <li><strong>Ev:</strong> Salon, oturma odası, çalışma odası ve yatak odası.</li>
  <li><strong>Ofis:</strong> Masa başı ve toplantı alanlarında lokal ısınma.</li>
  <li><strong>Kafe ve restoran:</strong> Kapalı salonlardaki oturma alanları; duvara yatay montajla zeminde yer kaplamaz.</li>
  <li><strong>Mağaza ve iş yeri:</strong> Kasa, bekleme ve giriş bölümleri.</li>
</ul>
<p>Bu model için bir su ve nem koruma sınıfı belirtilmemiştir; yağmur, kar veya doğrudan neme maruz kalan açık alanlarda kullanmayın. Açık teras ve bahçe için <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar">dış mekân ısıtıcılarını</a> inceleyin.</p>

<h3>Elektrik Tüketimi Nasıl Hesaplanır?</h3>
<p>Isıtıcının tüketimi <strong>güç (kW) × çalışma süresi (saat)</strong> formülüyle hesaplanır. R-3001 en yüksek kademede saatte en fazla <strong>2,5 kWh</strong> elektrik harcar. Bu değeri faturanızdaki kWh birim fiyatıyla çarparak saatlik maliyeti bulabilirsiniz. Düşük kademede çalıştırmak ve termostatın ayarlanan sıcaklığa ulaşınca ısıtmayı düzenlemesi, gerçek tüketimi en yüksek değerin altında tutar.</p>

<h3>Kurulum ve Güvenli Kullanım</h3>
<ul>
  <li>Ayaklı kullanımda cihazı düz ve sağlam bir zemine yerleştirin.</li>
  <li>Duvara yatay montajda kutudan çıkan askı aparatını kullanın; montaj yüksekliği ve mesafeler için kullanım kılavuzuna uyun.</li>
  <li>Perde, koltuk kumaşı, battaniye ve yanıcı malzemeleri cihazın önünden uzak tutun; cihazın üzerini örtmeyin.</li>
  <li>2500W güç için cihazı uzatma kablosu veya çoklu priz yerine doğrudan topraklı prize takın.</li>
  <li>Çalışırken gözetimsiz bırakmayın; çocukları ve evcil hayvanları sıcak yüzeyden uzak tutun.</li>
</ul>

<h3>Sık Sorulan Sorular</h3>
<h4>Renato R-3001 kaç watt?</h4>
<p>En yüksek güç 2500 W’tır. 6 kademeli ısı programıyla ısı seviyesi ihtiyaca göre düşürülebilir.</p>
<h4>Karbon ısıtıcı ne kadar elektrik harcar?</h4>
<p>R-3001 en yüksek kademede saatte en fazla 2,5 kWh harcar. Saatlik maliyet için bu değeri elektrik faturanızdaki kWh birim fiyatıyla çarpın; düşük kademe ve termostat kullanımı tüketimi azaltır.</p>
<h4>Karbon ısıtıcı zararlı mı?</h4>
<p>Karbon ısıtıcılar elektrikle çalışır; yanma olmadığı için karbonmonoksit, duman veya koku üretmez ve ortamdaki oksijeni tüketmez. Güvenli kullanım için cihazla aranızda mesafe bırakın, önünü yanıcı eşyalardan uzak tutun ve kullanım kılavuzundaki uyarılara uyun.</p>
<h4>Duvara monte edilebilir mi?</h4>
<p>Evet. Kutudan çıkan duvar askı aparatıyla yatay olarak monte edilebilir; ayağıyla dikey olarak da kullanılabilir.</p>
<h4>Termostat ne işe yarar?</h4>
<p>Termostat, ayarladığınız sıcaklığa ulaşıldığında ısıtmayı düzenler. Böylece ortam gereğinden fazla ısınmaz ve cihaz sürekli tam güçte çalışmaz.</p>
<h4>Garanti süresi ne kadar?</h4>
<p>Renato R-3001 2 yıl garantilidir. Garanti ve teknik destek için <a href="/iletisim">bize ulaşabilirsiniz</a>.</p>

<p>Farklı modelleri karşılaştırmak için <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar/dikey-ev-tipi-isiticilar">dikey ev tipi ısıtıcılar</a> kategorisine, tüm seçenekler için <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar">elektrikli ısıtıcılar</a> sayfasına veya <a href="/marka/renato">Renato marka sayfasına</a> göz atabilirsiniz.</p>
HTML;
    }

    private function brandDescriptionHtml(): string
    {
        return <<<'HTML'
<h2>Renato Elektrikli Isıtıcılar</h2>
<p><strong>Renato</strong>, ev ve iş yerleri için uzaktan kumandalı, termostatlı elektrikli ısıtıcılar sunan bir markadır. Koşar Ticaret’te Renato ürünleri teknik özellikleri, garanti bilgisi ve güncel fiyatıyla listelenir.</p>
<p>Serinin ilk modeli <a href="/urun/renato-r-3001-2500w-kumandali-dikey-karbon-isitici-6-kademe-termostatli"><strong>Renato R-3001</strong></a>; 2500W karbon rezistans, 6 kademeli ısı programı, termostat ve dijital ekranla gelir. Ayaklı dikey konumda veya kutudan çıkan askı aparatıyla duvara yatay olarak kullanılabilir.</p>
<p>Diğer modeller için <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar">ev tipi ısıtıcılar</a> ve <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar">elektrikli ısıtıcılar</a> kategorilerine göz atabilir, model seçiminde destek için <a href="/iletisim">bize ulaşabilirsiniz</a>.</p>
HTML;
    }

    /** @return list<array{q: string, a: string}> */
    private function brandFaq(): array
    {
        return [
            [
                'q' => 'Renato ısıtıcıların garanti süresi ne kadar?',
                'a' => 'Koşar Ticaret’te satılan Renato R-3001 karbon ısıtıcı 2 yıl garantilidir.',
            ],
            [
                'q' => 'Renato R-3001 duvara monte edilebilir mi?',
                'a' => 'Evet. Kutudan çıkan duvar askı aparatıyla yatay olarak monte edilebilir; ayağıyla dikey olarak da kullanılabilir.',
            ],
            [
                'q' => 'Renato R-3001 dış mekânda kullanılır mı?',
                'a' => 'Model için su ve nem koruma sınıfı belirtilmediğinden yağmur ve neme maruz kalan açık alanlarda kullanılmamalıdır. Ev, ofis, kafe ve mağaza gibi iç mekânlar için uygundur.',
            ],
        ];
    }
};
