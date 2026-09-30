<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const HS = '/kategoriler/hidrofor-sistemleri';

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $changed = 0;

        foreach ($this->content() as $id => $fields) {
            $current = DB::table('categories')->where('id', $id)->first(['description', 'buying_guide', 'faq', 'meta_description']);
            if ($current === null) {
                $this->misses[$id][] = 'kategori yok';

                continue;
            }

            $data = [];

            foreach (['description', 'buying_guide', 'meta_description'] as $column) {
                if (! isset($fields[$column])) {
                    continue;
                }
                [$expectedPrefix, $value] = $fields[$column];
                $old = trim((string) $current->{$column});
                if ($old === trim($value)) {
                    continue;
                }
                $matches = $expectedPrefix === '' ? $old === '' : str_starts_with($old, $expectedPrefix);
                if (! $matches) {
                    $this->misses[$id][] = $column;

                    continue;
                }
                $data[$column] = $value;
            }

            if (isset($fields['faq'])) {
                [$expectedFirstQuestion, $faq] = $fields['faq'];
                $newFaq = json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $oldFaq = json_decode((string) $current->faq, true);
                if ($current->faq !== $newFaq) {
                    if (($oldFaq[0]['q'] ?? null) === $expectedFirstQuestion) {
                        $data['faq'] = $newFaq;
                    } else {
                        $this->misses[$id][] = 'faq';
                    }
                }
            }

            if ($data !== []) {
                DB::table('categories')->where('id', $id)->update($data + ['updated_at' => now()]);
                $changed++;
            }
        }

        if ($changed > 0) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        // İçerik güncellemesi geri alınmaz.
    }

    /** @return array<int, array<string, array{0: string, 1: mixed}>> */
    private function content(): array
    {
        $hs = self::HS;

        return [
            119 => [
                'description' => ['<h2>Hidrofor Sistemi Çeşitleri ve Fiyatları</h2>', <<<HTML
<h2>Hidrofor Sistemi Çeşitleri ve Fiyatları</h2>
<p><strong>Hidrofor sistemi</strong>, şebeke basıncının yetmediği veya suyun depodan ya da kuyudan alındığı ev, apartman ve işletmelerde musluklara düzenli basınçla su veren pompa setidir. Pompa, basınç tankı veya hidromat ve otomatik kontrol (presostat, basınç sensörü veya frekans invertörü) birlikte çalışır.</p>
<p>Bu kategoride Sumak, Pedrollo, Winpo ve Kaysu markalarının hidroforları yer alır. Ürünler tek daireli evlerden çok katlı bina ve sitelere kadar uzanır.</p>
<h3>Hidrofor Sistemi Türleri</h3>
<ul>
<li><strong><a href="{$hs}/hidroforlar">Hidroforlar</a>:</strong> Paket, bina ve grup hidroforlarının birlikte listelendiği ana liste.</li>
<li><strong><a href="{$hs}/ev-tipi-hidroforlar">Ev tipi hidroforlar</a>:</strong> Müstakil ev, villa ve küçük apartmanlar için tek pompalı paketler; hidromatlı veya 24–50 litre tanklı.</li>
<li><strong><a href="{$hs}/hidrofor-grubu">Hidrofor grubu</a>:</strong> Çok katlı bina, site ve otel için düşey milli kademeli pompalı sistemler; tek, çift ve üç pompalı ile frekans kontrollü seçenekler.</li>
<li><strong><a href="{$hs}/hidromat">Hidromat</a>:</strong> Pompayı akışa ve basınca göre çalıştırıp durduran tanksız kontrol cihazı; Pedrollo EASY SMALL II, Winpo WNP-10H ve Kaysu DSK2.2.</li>
<li><strong><a href="{$hs}/sicak-su-hidroforu">Sıcak su hidroforu</a>:</strong> Güneş enerjisi sistemlerinde sıcak su hattının basıncını artırmak için; Sumak SM 7-SH ve Winpo WNP 226.</li>
</ul>
<h3>Markaya Göre Hidroforlar</h3>
<ul>
<li><strong><a href="{$hs}/sumak-hidrofor">Sumak</a>:</strong> SM ve SMJ paket hidroforlar, SYMH yatay kademeli bina hidroforları ile SHM, SHT, SHTP ve SMKT hidrofor grupları.</li>
<li><strong><a href="{$hs}/pedrollo-hidrofor">Pedrollo</a>:</strong> PKm 60, JSWm, JCRm, 4CPm ve 2CPm paket hidroforlar; 2 kat 2 daireden 9 kat 20 daireye kadar.</li>
<li><strong><a href="/marka/winpo">Winpo</a>:</strong> WNP 100, 150 ve 200 paket hidroforlar ile WNP1 VM ve WNP2 VM tek ve iki pompalı hidroforlar.</li>
<li><strong><a href="/marka/kaysu">Kaysu</a>:</strong> Ev ve küçük binalar için paket hidroforlar; 1 daireden 8 kat 18 daireye kadar.</li>
</ul>
HTML],
                'buying_guide' => ['<h3>Hidrofor Sistemi Seçim Rehberi</h3>', <<<HTML
<h3>Hidrofor Sistemi Seçim Rehberi</h3>
<ul>
<li><strong>Kat ve daire sayısı:</strong> Ürün adındaki kat ve daire değeri ilk ölçüdür. Teknik tabloda aynı model için iki kombinasyon verilebilir; örneğin Sumak SHM12 A 220/4 için 9 kat 24 daire veya 4 kat 48 daire. Binaya en yakın kombinasyona bakılır.</li>
<li><strong>Basınç:</strong> Her 10 metre yükseklik yaklaşık 1 bar basınç demektir; bir kat yaklaşık 3 metre alınırsa kat başına yaklaşık 0,3 bar eklenir. En üst kattaki muslukta da kullanım için yeterli basınç kalmalıdır.</li>
<li><strong>Su kaynağı:</strong> Depo veya kuyu pompanın altındaysa kendinden emişli jet pompalı model seçilir (Sumak SMJ, Pedrollo JSWm ve JCRm). Emiş derinliği ürün sayfasında yazar; bu modellerde 7–9 metredir.</li>
<li><strong>Tanklı mı, hidromatlı mı:</strong> Hidromatlı modeller az yer kaplar ancak küçük su çekimlerinde de pompayı çalıştırır. Tanklı modellerde küçük çekimleri tank karşılar ve pompa daha seyrek devreye girer.</li>
<li><strong>Elektrik:</strong> Ev tipi paketler genellikle 220 V'tur. Büyük bina hidroforları ve gruplar çoğunlukla 380 V'tur.</li>
<li><strong>Yedeklilik:</strong> Suyun kesilmemesi gereken bina ve işletmelerde çift veya üç pompalı hidrofor grubu tercih edilir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/hidrofor-nedir-ne-ise-yarar-nasil-calisir">hidrofor nedir, nasıl çalışır</a>, <a href="/blog/apartman-icin-hidrofor-nasil-secilir">apartman için hidrofor seçimi</a> ve <a href="/blog/kac-katli-binaya-hangi-hidrofor">kaç katlı binaya hangi hidrofor</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Hidrofor sistemi ne işe yarar?', [
                    ['q' => 'Hidrofor sistemi ne işe yarar?', 'a' => 'Şebeke basıncı yetmediğinde veya su bir depodan ya da kuyudan alındığında musluklara düzenli basınçla su verir. Musluk açılınca pompa devreye girer, kapanınca basınç tamamlanır ve pompa durur. Suyu depolamaz; su kesintisine karşı ayrıca depo gerekir.'],
                    ['q' => 'Kaç katlı binaya hangi hidrofor gerekir?', 'a' => 'Ürün adındaki kat ve daire değeri seçimde ilk ölçüdür. Kataloğumuzda paket hidroforlar 2 kat 2 daireden 9 kat 20 daireye (Pedrollo 2CPm 25/14A), Sumak SYMH bina hidroforları 14 kata kadar uzanır. Daha yüksek binalar için hidrofor grupları 28 kata kadar seçenek sunar.'],
                    ['q' => 'Hidrofor neden çok sık açılıp kapanıyor?', 'a' => 'En sık neden basınç tankının ön basıncının düşmesi veya tank membranının yırtılmasıdır. Pompa kapalıyken tankın hava valfine kısa süre basıldığında hava yerine su geliyorsa membran yırtılmıştır. Ayrıntılar için <a href="/blog/hidrofor-start-stop-salinimi-kisa-cevrim">kısa çevrim</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Sıcak su için ayrı hidrofor gerekir mi?', 'a' => 'Evet. Standart hidroforlarda en yüksek su sıcaklığı ürün sayfasında belirtilir; örneğin Pedrollo paket hidroforlarda 40°C, Sumak SM modellerinde 45°C\'dir. Güneş enerjisi sistemlerindeki sıcak su hattı için <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforları</a> kullanılır.'],
                    ['q' => 'Hidromat ile hidrofor arasındaki fark nedir?', 'a' => 'Hidromat, pompayı akışa ve basınca göre çalıştırıp durduran tanksız bir kontrol cihazıdır; az yer kaplar. Tanklı hidroforda küçük su çekimlerini tank karşılar, pompa daha seyrek devreye girer. Ayrıntılar için <a href="/blog/hidrofor-hidromat-farki">hidromat ve hidrofor farkı</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
            120 => [
                'buying_guide' => ['', <<<HTML
<h3>Pedrollo Hidrofor Nasıl Seçilir?</h3>
<ul>
<li><strong>Kat ve daire:</strong> Ürün adındaki değer ilk ölçüdür. PKm 60 2 kat 2 daire, JCRm 1A 2 kat 4 daire, JSWm 2CX 4 kat 6 daire, 4CPm 80-C 4 kat 8 daire, 4CPm 100-C 5 kat 12 daire, JSWm 2AX 6 kat 10 daire; 2CPm 25/130N 4 kat 10 daire, 25/14B 7 kat 14 daire ve 25/14A 9 kat 20 daire içindir.</li>
<li><strong>Su kaynağı:</strong> Depo veya kuyu pompanın altındaysa emiş derinliğine bakılır. Ürün sayfalarına göre JSWm ve JCRm modellerinde 9 metre, 2CPm modellerinde 7 metre, PKm 60'ta 6 metredir.</li>
<li><strong>Gövde:</strong> JCRm paslanmaz gövdelidir; JSWm ve PKm 60 döküm gövdelidir.</li>
<li><strong>Kontrol tipi:</strong> Dar alanda hidromatlı, eşzamanlı kullanımın yoğun olduğu evlerde 24 veya 50 litre tanklı model öne çıkar. Dijital modellerde elektronik kontrol ünitesi bulunur.</li>
<li><strong>Ses:</strong> 4CPm modelleri ürün adında sessiz olarak geçer; bazı dijital modellerin ürün sayfasında ses seviyesi 50 dB altı olarak verilir.</li>
<li><strong>Elektrik:</strong> Bu kategorideki Pedrollo paket hidroforların tamamı 220 V'tur; model adındaki "m" harfi monofazeyi gösterir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/jet-pompa-hidrofor-baglanti-rehberi">jet pompa hidrofor bağlantısı</a> ve <a href="/blog/hidrofor-basinc-tanki-onsarj-ayari">basınç tankı ön şarj ayarı</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Pedrollo hidrofor kaç katlı binaya yeter?', [
                    ['q' => 'Pedrollo hidrofor kaç katlı binaya yeter?', 'a' => 'Ürün adındaki kat ve daire değeri seçimde ilk ölçüdür. Bu kategoride <strong>PKm 60</strong> 2 kat ve 2 daire, <strong>JSWm 2CX</strong> 4 kat ve 6 daire, <strong>JSWm 2AX</strong> 6 kat ve 10 daire, <strong>JCRm 2A</strong> 5-6 kat ve 10 daire, <strong>4CPm 100-C</strong> 5 kat ve 12 daire, <strong>2CPm 25/14A</strong> ise 9 kat ve 20 daire için önerilir. Bina daha büyükse veya eşzamanlı kullanım çok yüksekse <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a> değerlendirilmelidir.'],
                    ['q' => 'Hidromatlı mı, tanklı Pedrollo hidrofor mu seçmeliyim?', 'a' => 'Hidromatlı modeller tanksızdır; az yer kaplar ve pompayı akışa göre çalıştırıp durdurur, ancak küçük su çekimlerinde de pompa devreye girer. <strong>24 veya 50 litre tanklı</strong> modellerde tank bu küçük çekimleri karşılar; pompa daha seyrek çalışır ve basınç dalgalanması azalır. Birden fazla banyo ve mutfak aynı anda kullanılıyorsa tanklı model, dar bir alana kurulum gerekiyorsa hidromatlı model daha uygundur.'],
                    ['q' => 'JSWm ile JCRm arasındaki fark nedir?', 'a' => 'İkisi de kendinden emişli jet pompalı paket hidrofordur; pompa seviyesinin altındaki depodan veya sığ kuyudan su emebilir. <strong>JSWm</strong> döküm gövdelidir. <strong>JCRm</strong> paslanmaz gövdelidir ve korozyona daha dayanıklıdır. Kat ve daire değerleri iki seride yakındır; seçim su koşullarına ve bütçeye göre yapılır.'],
                    ['q' => '4CPm ve 2CPm hangi binalar için uygundur?', 'a' => '<strong>4CPm</strong> çok çarklı ve sessiz çalışan bir pompadır; 4-5 katlı, 8-12 daireli binalarda gürültünün önemli olduğu kurulumlar için uygundur. <strong>2CPm</strong> çift çarklı santrifüj pompalı, dijital kontrollü ve 50 litre tanklı paketlerdir; 4 kat 10 daireden 9 kat 20 daireye kadar daha yüksek binalar için sunulur. Depo pompa seviyesinin altındaysa emme hattı ve dip klapesi doğru kurulmalıdır.'],
                    ['q' => 'Pedrollo hidrofor tank ön basıncı ne olmalı?', 'a' => 'Tanklı modellerde tankın hava tarafı basıncı, pompanın devreye girme basıncının yaklaşık <strong>0,2-0,3 bar altında</strong> olmalıdır. Ölçüm, pompa kapalıyken ve tesisattaki su boşaltılmışken tank üzerindeki valften manometreyle yapılır. Ön basınç düşükse pompa sık devreye girer; bu kontrolün yılda bir yapılması önerilir.'],
                ]],
            ],
            124 => [
                'description' => ['<h2>Hidrofor Grubu Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Hidrofor Grubu Modelleri ve Fiyatları</h2>
<p><strong>Hidrofor grubu</strong>, çok katlı apartman, site, otel ve iş merkezlerinde tüm binaya düzenli basınçla su veren; bir veya birden fazla kademeli pompanın ortak kollektör ve kumanda panosuyla çalıştığı basınçlandırma sistemidir. Sumak modellerinde adındaki A harfi tek, B harfi çift, C harfi üç pompalı grubu gösterir.</p>
<p>Bu kategoride Sumak ve Winpo markalarının hidrofor grupları yer alır.</p>
<h3>Hidrofor Grubu Modelleri</h3>
<ul>
<li><strong>Sumak SHM (220 V) ve SHT (380 V):</strong> Düşey milli kademeli pompalı gruplar. SHM6, SHM8, SHM12 ile SHT6, SHT8, SHT12 ve SHT16 modellerinin adlarında 4 kattan 28 kata kadar kat ve daire değerleri yer alır; motor gücü 0.75–6.3 kW'tır.</li>
<li><strong>Sumak SHT 24 ve SHT 34:</strong> 380 V ve 5.5–11 kW; yüksek debili gruplar.</li>
<li><strong>Sumak SHT 32, 40, 50 ve 65:</strong> Motor gücü ve basma yüksekliği projeye göre seçilen seriler; 3–55 kW ve modele göre saatte 300 m³'e kadar debi.</li>
<li><strong>Sumak SHTP ve SHTPD:</strong> SHTP paslanmaz kademeli, SHTPD paslanmaz çark ve difüzörlü gruplardır. SHTP8 ve SHTPD8 1.6–3 kW, SHTP16 ve SHTPD16 4–5.5 kW; 7 kattan 22 kata kadar.</li>
<li><strong>Sumak frekans kontrollü (FK) modeller:</strong> SHT-A, SHT-B ve SHT-C FK serileri ile SHT6, SHT8, SHT12 ve SHT16'nın FK versiyonları.</li>
<li><strong>Sumak SMINOX12:</strong> Emişli paslanmaz kademeli; 220 V ve 380 V, 1.6–2.2 kW, 7 kat 14 daireden 11 kat 42 daireye kadar.</li>
<li><strong>Sumak SMKTA, SMKTB ve SMKTC:</strong> Emişli çift kademeli; 380 V, 4–5.5 kW ve 6 metre emiş derinliği.</li>
<li><strong>Winpo WNP1 VM ve WNP2 VM:</strong> Dik milli kademeli pompalı tek (WNP1) ve iki pompalı (WNP2) hidroforlar. M harfi 220 V, T harfi 380 V modeli gösterir; WNP1 VM modelleri 1.5–7.5 HP'dir.</li>
</ul>
<p>Müstakil ev ve küçük binalar için <a href="{$hs}/ev-tipi-hidroforlar">ev tipi hidroforlar</a> sayfasına, markaya göre seçim için <a href="/marka/sumak">Sumak</a> ve <a href="/marka/winpo">Winpo</a> sayfalarına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Hidrofor Grubu Nasıl Seçilir?</h3>
<ul>
<li><strong>Kat ve daire:</strong> Ürün adındaki değer ilk ölçüdür. Teknik tabloda iki kombinasyon verilebilir; örneğin Sumak SHT16 A 750/6 için 20 kat 34 daire veya 10 kat 64 daire.</li>
<li><strong>Basma yüksekliği:</strong> Bina yüksekliğine en üst katta gereken basınç ve boru kayıpları eklenir. Her 10 metre yükseklik yaklaşık 1 bar demektir.</li>
<li><strong>Debi:</strong> Daire sayısı ve eşzamanlı kullanım arttıkça debi ihtiyacı büyür. Sumak modellerinde B ve C gruplarının debisi, aynı serideki A modelinin yaklaşık iki ve üç katıdır.</li>
<li><strong>Pompa sayısı:</strong> Tek pompalı grupta pompa arızalandığında veya bakıma alındığında su kesilir. Çift ve üç pompalı gruplarda bir pompa devre dışıyken diğerleri su vermeye devam eder.</li>
<li><strong>Kontrol:</strong> Sabit hızlı gruplarda pompalar basınca göre devreye girip çıkar. Frekans kontrollü (FK) modellerde pompa hızı kullanıma göre değişir ve basınç daha dengeli kalır.</li>
<li><strong>Su kaynağı:</strong> Depo pompa seviyesinin altındaysa emişli modeller (SMINOX12, SMKT) tercih edilir veya emme hattı dip klapesiyle doğru kurulur.</li>
<li><strong>Elektrik:</strong> SHM ve SMINOX12 modellerinde 220 V seçeneği vardır; büyük gruplar 380 V'tur.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/hidrofor-grubu-secim-rehberi">hidrofor grubu seçimi</a>, <a href="/blog/hidrofor-grubu-boyutlandirma-rehberi">debi ve basınç hesabı</a> ve <a href="/blog/frekans-invertorlu-hidrofor-grubu">frekans invertörlü hidrofor grubu</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Hidrofor grubu ile tek pompalı hidrofor arasındaki fark nedir?', [
                    ['q' => 'Hidrofor grubu ile ev tipi hidrofor arasındaki fark nedir?', 'a' => 'Ev tipi hidrofor müstakil ev ve küçük binalar için tek pompalı, hazır bir pakettir. Hidrofor grubu ise çok katlı binalar için kademeli pompalı, kollektörlü ve panolu bir sistemdir; çift ve üç pompalı modellerde yedeklilik sağlar. Ayrıntılar için <a href="/blog/hidrofor-grubu-ev-tipi-karsilastirma">karşılaştırma</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Sumak model adlarındaki harf ve sayılar ne anlama gelir?', 'a' => 'A harfi tek, B harfi çift, C harfi üç pompalı grubu gösterir. Harften sonraki ilk sayı motor gücünü belirtir; örneğin 220 yaklaşık 2.2 HP, 550 yaklaşık 5.5 HP, 1000 yaklaşık 10 HP\'lik pompayı gösterir. Adın sonundaki FK ise frekans kontrollü modeldir.'],
                    ['q' => 'Frekans kontrollü (FK) hidrofor grubu ne sağlar?', 'a' => 'Pompa hızı su kullanımına göre ayarlanır. Böylece basınç daha dengeli kalır, pompalar daha az dur-kalk yapar ve düşük kullanımda enerji tüketimi azalır. Tasarrufun büyüklüğü binanın kullanım profiline bağlıdır.'],
                    ['q' => 'Tek pompalı (A) grup yeterli olur mu?', 'a' => 'Kapasite açısından yeterli olabilir; örneğin Sumak SHT16 A 850/8 ürün adında 28 kat 28 daire için verilir. Ancak tek pompada arıza veya bakım sırasında su kesilir. Kesintinin kabul edilmediği binalarda çift (B) veya üç (C) pompalı grup tercih edilmelidir.'],
                    ['q' => 'Hidrofor grubunda bakım neleri kapsar?', 'a' => 'Basınç tankının ön basıncı, presostat veya basınç sensörü ayarları, pompa salmastralarında sızıntı ve kumanda panosunun pompaları sırayla çalıştırıp çalıştırmadığı kontrol edilir. Bakım aralığı kullanım yoğunluğuna göre servisle planlanır. Ayrıntılar için <a href="/blog/hidrofor-grubu-ariza-bakim-rehberi">arıza ve bakım rehberi</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
            125 => [
                'buying_guide' => ['', <<<HTML
<h3>Sumak Hidrofor Nasıl Seçilir?</h3>
<ul>
<li><strong>Kat ve daire:</strong> Ürün adındaki değer ilk ölçüdür. SM5 2 kat 2 daire, SM 10 ve SMJ 85 4 kat 4 daire, SM 15 ve SMJ 100 4 kat 6 daire, SMJ 150 5 kat 10 daire, SMJ 220 8 kat 16 daire içindir. SYMH modelleri 6 kat 13 daireden 14 kat 20 daireye ya da 6 kat 34 daireye kadar uzanır.</li>
<li><strong>Su kaynağı:</strong> Depo veya kuyu pompanın altındaysa kendinden emişli SMJ jet pompalı modeller seçilir. Ürün sayfalarına göre emiş derinliği SMJ 85'te 7 metre, SMJ 100, 150 ve 220'de 9 metre, SM ve SYMH modellerinde 6 metredir.</li>
<li><strong>Tank:</strong> Paket hidroforlar hidromatlı, 24 veya 50 litre tanklıdır; SYMH bina hidroforlarında 100 litre tank bulunur.</li>
<li><strong>Elektrik:</strong> SM, SMJ ve SYMH modelleri 220 V'tur. SYMTH modelleri 380 V'tur; SYMH6-100/6'nın da 380 V versiyonu vardır.</li>
<li><strong>Ses:</strong> Sessiz çalışma öncelikliyse kabinli ve frekans kontrollü SMH 120 BOX değerlendirilebilir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/ev-tipi-hidrofor-rehberi-mustakil-ev-villa">ev tipi hidrofor rehberi</a> ve <a href="/blog/hidrofor-presostat-histerezis-set-noktalari">presostat ayarı</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
            ],
        ];
    }
};
