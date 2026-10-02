<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\PublicPageCache;
use App\Support\RichContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SKU = 'R-3001';

    private const SLUG = 'renato-r-3001-2500w-kumandali-dikey-karbon-isitici-6-kademe-termostatli';

    /** @var array<string, string> görsel yolu => alt metin */
    private array $alts = [
        'products/renato/01-renato-r-3001-dikey-karbon-isitici-on-gorunum.jpg' => 'Renato R-3001 2500W dikey karbon ısıtıcı, rezistans çalışırken önden',
        'products/renato/02-renato-r-3001-ayakli-isitici-acili-gorunum.jpg' => 'Renato R-3001 kumandalı dikey ısıtıcı, ayaklı kullanımda açılı görünüm',
        'products/renato/03-renato-r-3001-karbon-isitici-dijital-ekran.jpg' => 'Renato R-3001 karbon ısıtıcının gövde ekranında seçili kademe',
        'products/renato/04-renato-r-3001-karbon-rezistans-yan-gorunum.jpg' => 'Renato R-3001 infrared ısıtıcı, karbon rezistans tüpü yandan',
        'products/renato/05-renato-r-3001-uzaktan-kumanda.jpg' => 'Renato R-3001 uzaktan kumanda: açma-kapama, kademe okları, Max ve Min',
        'products/renato/06-renato-r-3001-kontrol-paneli-yakin-cekim.jpg' => 'Renato R-3001 gövdesindeki ekranlı panel ve açma-kapama tuşu',
        'products/renato/07-renato-r-3001-rezistans-koruma-izgarasi.jpg' => 'Renato R-3001 karbon rezistans tüpü ve metal koruma teli yakından',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $product = Product::query()->where('sku', self::SKU)->orWhere('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        $product->forceFill([
            'description' => RichContent::normalize($this->descriptionHtml()),
            'image_alt' => $this->alts[$product->image] ?? $product->image_alt,
        ])->save();

        if (Schema::hasTable('product_images')) {
            foreach ($this->alts as $path => $alt) {
                ProductImage::query()->where('product_id', $product->id)->where('path', $path)->update(['alt' => $alt]);
            }
        }

        PublicPageCache::forgetAll();
    }

    public function down(): void
    {
        // İçerik güncellemesi; önceki metin 2026_09_29_130000 migration'ında duruyor.
    }

    private function descriptionHtml(): string
    {
        return <<<'HTML'
<h2>Renato R-3001 Dikey Karbon Isıtıcı: 2500W Güç, Uzaktan Kumanda ve Termostat</h2>
<p>Kışın evin bir köşesi hep serin kalıyorsa ya da ofiste yalnızca masa başını ısıtmak istiyorsanız <strong>Renato R-3001</strong> bu iş için tasarlandı. <strong>2500W karbon rezistanslı infrared ısıtıcı</strong> olan R-3001, havayı değil karşısındaki kişiyi ve yüzeyleri ısıtır; açtıktan kısa süre sonra önünde sıcaklığı hissedersiniz. <strong>6 kademeli ısı programı</strong> ve <strong>termostat</strong> ısıyı ihtiyaca göre dengeler, <strong>uzaktan kumanda</strong> ile ayarları oturduğunuz yerden değiştirirsiniz.</p>

<h3>Günlük Kullanımda R-3001</h3>
<ul>
  <li><strong>Isıyı siz belirlersiniz:</strong> 6 kademe arasından hafif serin akşamlar için düşük, soğuk günler için yüksek seviye seçilir; termostat ayarlanan sıcaklığa ulaşınca ısıtmayı düzenler.</li>
  <li><strong>İki farklı yerleşim:</strong> Ayağıyla dikey durur ya da kutudan çıkan askı aparatıyla duvara yatay monte edilir. Yükseklik ve açı ayarı sayesinde ısıyı oturma alanına yöneltirsiniz.</li>
  <li><strong>Sessiz ortam:</strong> Fan ve pervane olmadığı için uğultu yapmaz, odadaki tozu havalandırmaz; çalışma, kitap okuma ve dinlenme sırasında rahatsız etmez.</li>
  <li><strong>Hızlı hissedilen ısı:</strong> 2500W güçteki karbon rezistans, odanın tamamının ısınmasını beklemeden önündeki bölgeyi ısıtır.</li>
</ul>

<h3>Kumanda ve Gövde Paneli</h3>
<p>Uzaktan kumandada açma-kapama tuşu, kademeyi artırıp azaltan ok tuşları ve <strong>Max</strong> / <strong>Min</strong> tuşları bulunur. Gövdenin alt bölümündeki ekranda seçili kademe görünür; ekranın altındaki tuşla cihaz kumandasız da açılıp kapatılabilir.</p>

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

<h3>En İyi Sonuç İçin Doğru Konumlandırma</h3>
<p>Infrared ısı ışık gibi düz bir hatta ilerler ve önüne çıkan yüzeyi ısıtır. Bu nedenle R-3001'in verimi, nereye baktığına bağlıdır. Cihazı oturduğunuz koltuğa, çalışma masanıza veya müşterilerin beklediği alana dönük yerleştirin; arada eşya bırakmayın. Geniş bir salonun her noktasını eşit ısıtmak yerine kullanılan bölgeyi ısıtmak için idealdir.</p>
<ul>
  <li><strong>Evde:</strong> Salonda koltuk yanında, çalışma odasında masa kenarında veya yatak odasında ek ısıtıcı olarak.</li>
  <li><strong>Ofiste:</strong> Kaloriferin yetişmediği masa başlarında ve toplantı alanlarında.</li>
  <li><strong>Kafe, restoran ve mağazada:</strong> Kapalı salondaki masa grupları, kasa önü ve bekleme bölümleri; duvara yatay montajla zeminde yer kaplamaz.</li>
</ul>
<p>R-3001 için su ve nem koruma sınıfı belirtilmemiştir. Yağmur, kar veya doğrudan nem alan açık balkon, teras ve bahçede kullanmayın; bu alanlar için <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/dis-mekan-isiticilar">dış mekân ısıtıcıları</a> daha uygundur.</p>

<h3>Kullanım Süresine Göre Elektrik Tüketimi</h3>
<p>Tüketimi bulmak için cihaz gücünü (kW) çalışma süresiyle (saat) çarpmanız yeterlidir. R-3001 en yüksek kademede tam güçte çalıştığında:</p>
<ul>
  <li>1 saatte en fazla <strong>2,5 kWh</strong>,</li>
  <li>4 saatte en fazla <strong>10 kWh</strong>,</li>
  <li>8 saatte en fazla <strong>20 kWh</strong> elektrik harcar.</li>
</ul>
<p>Maliyeti öğrenmek için bu değeri faturanızdaki kWh birim fiyatıyla çarpın. Daha düşük kademede çalıştırmak ve termostatın ısıtmayı düzenlemesi, gerçek tüketimi bu üst sınırın altında tutar.</p>

<h3>Güvenli Kullanım İçin</h3>
<ul>
  <li>Cihazı uzatma kablosu veya çoklu priz yerine doğrudan topraklı bir prize takın; 2500W güç için bu önemlidir.</li>
  <li>Ayaklı kullanırken düz ve sağlam bir zemin seçin, devrilme riski olan yerlerden kaçının.</li>
  <li>Duvar montajında yalnızca kutudan çıkan askı aparatını kullanın; yükseklik ve mesafe ölçüleri için kullanım kılavuzunu takip edin.</li>
  <li>Perde, battaniye, koltuk kumaşı gibi yanabilecek eşyaları cihazın önünden uzak tutun ve üzerini kesinlikle örtmeyin.</li>
  <li>Çocukların ve evcil hayvanların sıcak yüzeye dokunmasını önleyin; cihazı açık bırakıp ortamdan ayrılmayın.</li>
</ul>

<h3>Sık Sorulan Sorular</h3>
<h4>Renato R-3001 kaç watt?</h4>
<p>R-3001'in en yüksek gücü 2500 W'tır. Daha az ısı gerektiğinde 6 kademeden düşük olanlardan birini seçebilirsiniz.</p>
<h4>Fanlı ısıtıcıdan farkı nedir?</h4>
<p>Fanlı ısıtıcılar havayı ısıtıp odaya üfler; R-3001 ise kızılötesi ışınımla doğrudan karşısındaki kişiyi ve eşyaları ısıtır. Pervanesi olmadığı için sessiz çalışır ve tozu dolaştırmaz.</p>
<h4>Karbon ısıtıcı sağlığa zararlı mı?</h4>
<p>R-3001 elektrikle çalışır; içinde yanma olmadığından ortama gaz, duman veya koku vermez ve odadaki oksijeni tüketmez. Cihazla aranızda mesafe bırakmanız ve kullanım kılavuzundaki uyarılara uymanız yeterlidir.</p>
<h4>Duvara monte edilebilir mi?</h4>
<p>Evet. Kutudan çıkan askı aparatıyla duvara yatay olarak takılır. Duvara monte etmek istemezseniz ayağıyla dikey olarak kullanabilirsiniz.</p>
<h4>Banyoda veya açık balkonda kullanılır mı?</h4>
<p>Önerilmez. Model için su ve nem koruma sınıfı belirtilmediğinden nemli ortamlarda ve yağmur alan açık alanlarda kullanılmamalıdır.</p>
<h4>Garanti süresi ne kadar?</h4>
<p>Renato R-3001 2 yıl garantiyle satılır. Kurulum, kullanım veya garanti konusunda <a href="/iletisim">bize ulaşabilirsiniz</a>.</p>

<p>Başka modellere de bakmak isterseniz <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar/ev-tipi-isiticilar/dikey-ev-tipi-isiticilar">dikey ev tipi ısıtıcılar</a>, <a href="/kategoriler/isitma-sistemleri/elektrikli-isiticilar">elektrikli ısıtıcılar</a> ve <a href="/marka/renato">Renato marka sayfası</a> işinize yarayacaktır.</p>
HTML;
    }
};
