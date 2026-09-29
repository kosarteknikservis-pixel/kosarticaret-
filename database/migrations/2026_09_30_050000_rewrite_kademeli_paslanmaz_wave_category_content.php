<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SP = '/kategoriler/su-pompalari';

    private const SK = '/kategoriler/su-pompalari/kademeli-pompalar';

    private const SF = '/kategoriler/su-pompalari/santrifuj-pompalar';

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
        $sp = self::SP;
        $sk = self::SK;
        $sf = self::SF;

        return [
            134 => [
                'meta_description' => ['Kimyasal sıvılar, havuz ve tuzlu su için AISI 304/316L', 'AISI 304 ve AISI 316 paslanmaz pompalar: Pedrollo CP-ST, Sumak SMINOX, Winpo WF, CMI, CMF, BLC ve DWK. Havuz, arıtma, tuzlu su ve uyumlu kimyasallar için.'],
                'description' => ['<h2>Paslanmaz Pompa (Kimyasal Pompa) Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Paslanmaz Pompa (Kimyasal Pompa) Modelleri ve Fiyatları</h2>
<p><strong>Paslanmaz pompa</strong>, suyla temas eden gövde, çark ve mil gibi parçaları paslanmaz çelikten üretilen pompadır. Döküm gövdenin paslanabileceği veya suyu kirletebileceği uygulamalarda; havuz ve arıtma tesislerinde, tuzlu veya klorlu su hatlarında, içme suyu tesisatında ve pompa malzemesiyle uyumlu kimyasal sıvıların transferinde kullanılır.</p>
<p>Bu kategoride Pedrollo, Sumak ve Winpo markalarının paslanmaz santrifüj, çok kademeli ve kimyasal pompaları yer alır.</p>
<h3>Paslanmaz Pompa Serileri</h3>
<ul>
<li><strong>Pedrollo CP-ST4 ve CP-ST6:</strong> Tamamı paslanmaz tek fanlı santrifüj pompalar; 1–3 HP, 32–45 mss basma, 7.2–18 m³/h debi ve 7 m emiş. ST4 AISI 304, ST6 AISI 316 versiyonudur; adında "m" bulunan CPm modelleri 220 V monofazedir.</li>
<li><strong>Sumak SMINOX/A ve SMINOX/K:</strong> Açık (A) veya kapalı (K) fanlı paslanmaz santrifüj pompalar; 1–4 HP ve 50 m³/h'e kadar debi. Adında X bulunan modeller AISI 316'dır.</li>
<li><strong>Sumak SMINOX 160 ve 200:</strong> Rijit kaplinli paslanmaz santrifüj pompalar; 2–10 HP, 380 V ve 125 m³/h'e kadar debi.</li>
<li><strong>Sumak SMINOX12 ve SMINOX-J:</strong> Yüksek basınçlı SMINOX12 ile kendinden emişli SMINOX-J jet modelleri; 1–3 HP ve 75 mss'ye kadar basma.</li>
<li><strong>Winpo WF4 ve WF6:</strong> Flanşlı, tamamı paslanmaz santrifüj pompalar; 3–30 HP, 22–130 m³/h debi ve 73 mss'ye kadar basma. WF4 AISI 304, WF6 AISI 316'dır.</li>
<li><strong>Winpo CMI ve CMF:</strong> AISI 304 paslanmaz çok kademeli pompalar; 1–3 HP ve 49–72 mss basma. CMF-SS6 modelleri AISI 316'dır.</li>
<li><strong>Winpo BLC ve DWK:</strong> AISI 304 paslanmaz gövdeli kimyasal pompalar; 0.5–4 HP.</li>
</ul>
<p>Suyun içine indirilen modeller için <a href="{$sp}/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj dalgıç pompalar</a>, genel su transferi için <a href="{$sf}">santrifüj pompalar</a> kategorisine bakabilirsiniz.</p>
<h3>Paslanmaz Pompa Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca paslanmaz çelik sınıfı (AISI 304 veya AISI 316), motor gücü, fan tipi, kademe sayısı, bağlantı tipi (dişli veya flanşlı) ve elektrik beslemesi belirler. Markaya göre incelemek için <a href="/marka/pedrollo">Pedrollo</a>, <a href="/marka/sumak">Sumak</a> ve <a href="/marka/winpo">Winpo</a> sayfalarını ziyaret edebilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Paslanmaz Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Sıvının cinsi:</strong> Kimyasal bir sıvı basılacaksa yalnızca gövdenin değil çark, mil, mekanik salmastra ve contaların da o sıvıya dayanıklı olması gerekir. Sıvının adı, derişimi ve sıcaklığı bilinmeden kimyasal pompa seçilmemelidir.</li>
<li><strong>AISI 304 mü, AISI 316 mı:</strong> Temiz su ve içme suyunda AISI 304 genellikle yeterlidir. Tuzlu su, deniz suyu ve klorür içeren sularda molibden katkılı AISI 316 daha dayanıklıdır.</li>
<li><strong>Sıcaklık:</strong> Sumak SMINOX/A ve SMINOX/K modellerinde sıvı sıcaklığı 0–45 °C, Winpo CMI, CMF ve WF modellerinde 90 °C'ye kadardır. Değer her ürün sayfasında belirtilir.</li>
<li><strong>Debi ve basma:</strong> Yüksek debi ve düşük basma için açık veya kapalı fanlı santrifüj ya da flanşlı WF modeller, yüksek basınç için CMI, CMF çok kademeli veya SMINOX12 modeller uygundur.</li>
<li><strong>Elektrik:</strong> Winpo'da adı M ile biten modeller 220 V, T ile bitenler 380 V'tur. Sumak'ta adında T bulunan modeller trifazedir.</li>
</ul>
<p>Malzeme seçimi için <a href="/blog/paslanmaz-santrifuj-pompa-secimi">paslanmaz santrifüj pompa seçimi</a> yazımızı okuyabilirsiniz. Kimyasal bir sıvı için sıvının adını ve derişimini bize iletirseniz uygun modeli birlikte belirleyebiliriz.</p>
HTML.self::CONTACT],
                'faq' => ['AISI 304 ve 316L paslanmaz pompa arasındaki fark nedir?', [
                    ['q' => 'AISI 304 ile AISI 316 paslanmaz pompa arasındaki fark nedir?', 'a' => 'AISI 316 molibden içerdiği için klorür ve tuzlu suya karşı AISI 304\'ten daha dayanıklıdır. Temiz su ve içme suyunda AISI 304 genellikle yeterlidir; tuzlu su, deniz suyu ve klorlu sularda AISI 316 tercih edilir. Bu kategoride Pedrollo ST6, Sumak SMINOX X, Winpo WF6 ve CMF-SS6 modelleri AISI 316\'dır.'],
                    ['q' => 'Kimyasal pompa seçerken gövdenin paslanmaz olması yeterli mi?', 'a' => 'Hayır. Gövdeyle birlikte çark, mil, mekanik salmastra ve contaların da basılacak sıvıya dayanıklı olması gerekir. Paslanmaz çelik her asit ve kimyasala dayanmaz; sıvının adı, derişimi ve sıcaklığı bilinmeden seçim yapılmamalıdır.'],
                    ['q' => 'Paslanmaz pompa havuzda kullanılabilir mi?', 'a' => 'Klorlu havuz suyunun transferinde paslanmaz pompalar kullanılabilir. Filtre sistemine bağlanacak sirkülasyon için ön filtreli havuz pompaları daha uygundur; bkz. <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi">ön filtreli havuz pompaları</a>.'],
                    ['q' => 'Paslanmaz pompa tuzlu suda paslanır mı?', 'a' => 'AISI 304 tuzlu suda zamanla noktasal korozyona uğrayabilir. Tuzlu su ve deniz suyunda AISI 316 modeller seçilmeli, kullanım sonrasında pompa tatlı suyla durulanmalıdır.'],
                    ['q' => 'Paslanmaz pompa gıda üretiminde kullanılabilir mi?', 'a' => 'Gıdayla temas eden uygulamalarda pompanın ve contaların gıdaya uygunluğu üretici belgesiyle doğrulanmalıdır. Süt, meyve suyu gibi hijyen gerektiren proseslerde bu kategorideki sanayi tipi pompalar yerine hijyenik tasarımlı pompalar gerekir.'],
                ]],
            ],
            139 => [
                'meta_description' => ['Dikey kademeli, monoblok yatay ve norm tipi', 'Dikey, monoblok yatay, norm tipi ve yatay kademeli pompalar: Sumak, Winpo, Pedrollo ve Kaysu modelleri. Bina basınçlandırma ve sanayi hatları için.'],
                'description' => ['<h2>Kademeli Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Kademeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Kademeli pompa</strong>, aynı mil üzerinde seri çalışan birden fazla çarkla suyun basıncını adım adım artıran santrifüj pompadır. Tek fanlı pompaların yetmediği yüksek basma gereken işlerde; çok katlı bina besleme, hidrofor grupları, basınçlı yıkama ve sanayi hatlarında kullanılır.</p>
<p>Bu kategoride Sumak, Winpo, Pedrollo ve Kaysu markalarının kademeli pompaları yer alır.</p>
<h3>Kademeli Pompa Çeşitleri</h3>
<ul>
<li><strong>Dikey kademeli pompalar:</strong> Sumak SHM, SHT ve SHTP, Winpo WNP VM ve CVL, Pedrollo MK serileri. Az yer kaplar; 200 mss'ye kadar basma sağlayan modeller vardır. Bkz. <a href="{$sk}/dikey-kademeli-pompalar">dikey kademeli pompalar</a>.</li>
<li><strong>Monoblok yatay kademeli pompalar:</strong> Sumak SYM ve SYMT ile paslanmaz SYMP ve SYMTP serileri; konut ve küçük tesis basınçlandırması içindir. Bkz. <a href="{$sk}/monoblok-yatay-kademeli">monoblok yatay kademeli pompalar</a>.</li>
<li><strong>Norm tipi yatay kademeli pompalar:</strong> Sumak SYT serisi; motoru kaplinle şaseye bağlanmış, 4–55 kW ve PN16 flanşlı sanayi pompaları. Bkz. <a href="{$sk}/norm-tipi-yatay-kademeli">norm tipi yatay kademeli pompalar</a>.</li>
<li><strong>Yatay kademeli pompalar:</strong> Pedrollo 3CR, 4CR ve 5CR, Kaysu HMC ve Winpo WNP SH modelleri; 40–82 mss basma veren kompakt pompalar. Bkz. <a href="{$sk}/yatay-kademeli-pompalar">yatay kademeli pompalar</a>.</li>
</ul>
<p>Pompa, basınç tankı ve kontrol panosu birlikte hazır istenirse <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a> daha uygun olabilir.</p>
<h3>Kademeli Pompa Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca kademe sayısı, motor gücü, gövde malzemesi (döküm veya paslanmaz), montaj tipi (dikey, monoblok veya kaplinli) ve elektrik beslemesi belirler. Ayrıntılar için <a href="/blog/kademeli-pompa-fiyatlari-2026-rehberi">kademeli pompa maliyetini belirleyen etkenler</a> yazımıza bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Kademeli Pompa Ne Zaman Tercih Edilir?</h3>', <<<HTML
<h3>Kademeli Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Pompa ile en yüksek kullanım noktası arasındaki kot farkına boru sürtünme kayıpları ve o noktada istenen basınç eklenir. 1 bar yaklaşık 10 metre su sütunudur; örneğin 10 katlı bir binada (yaklaşık 30 m) en üst katta 2 bar için sürtünme kayıplarıyla birlikte 55–60 metre civarında basma gerekir.</li>
<li><strong>Debi:</strong> Aynı anda kullanılan musluk ve daire sayısına göre m³/h cinsinden belirlenir. Kademeli pompada basma kademe sayısıyla artar; debiyi ise seri belirler.</li>
<li><strong>Montaj alanı:</strong> Dar makine dairelerinde dikey, yatay boru hatlarında ve kolay servis istenen yerlerde yatay, büyük güçlü sanayi hatlarında norm tipi modeller uygundur.</li>
<li><strong>Malzeme:</strong> İçme suyu ve paslanma istenmeyen tesisatlarda paslanmaz gövdeli seriler tercih edilir.</li>
<li><strong>Kontrol:</strong> Değişken tüketimli binalarda frekans invertörlü kontrol basıncı sabit tutar ve sık dur-kalkı azaltır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/kademeli-pompa-nedir-ne-ise-yarar-nasil-secilir">kademeli pompa nedir, nasıl seçilir</a> ve <a href="/blog/yuksek-bina-kademeli-pompa-boyutlandirma">yüksek binada kademeli pompa boyutlandırma</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Kademeli pompa neden daha yüksek basınç sağlar?', [
                    ['q' => 'Kademeli pompa nedir, ne işe yarar?', 'a' => 'Kademeli pompa, aynı mil üzerinde seri çalışan birden fazla çarkı olan santrifüj pompadır. Tek fanlı pompanın veremediği yüksek basıncı sağlar; çok katlı bina besleme, hidrofor grupları, basınçlı yıkama ve sanayi hatlarında kullanılır.'],
                    ['q' => 'Kademeli pompa neden daha yüksek basınç sağlar?', 'a' => 'Su çarklardan sırayla geçer ve her çarkın ürettiği basınç bir öncekinin üzerine eklenir. Kademe sayısı arttıkça basma yüksekliği ve gereken motor gücü artar; debi ise büyük ölçüde aynı kalır.'],
                    ['q' => 'Kademeli pompa mı, santrifüj pompa mı seçilmeli?', 'a' => 'Yüksek debi ve düşük basma gereken sulama ve transfer işlerinde tek fanlı santrifüj pompa, düşük veya orta debide yüksek basınç gereken işlerde kademeli pompa uygundur. Karşılaştırma için <a href="/blog/kademeli-pompa-mi-santrifuj-pompa-mi">kademeli pompa mı santrifüj pompa mı</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Dikey kademeli pompa ile yatay kademeli pompa arasındaki fark nedir?', 'a' => 'Dikey kademeli pompa az taban alanı kaplar ve dar makine dairelerine uygundur. Yatay kademeli pompa yatay boru hatlarına kolay bağlanır ve servis erişimi rahattır. Seçim çoğunlukla montaj alanına ve istenen debi ile basmaya göre yapılır.'],
                    ['q' => 'Frekans invertörlü kademeli pompa ne avantaj sağlar?', 'a' => 'İnvertör motor devrini anlık tüketime göre ayarlar. Böylece basınç sabit kalır, pompa sık dur-kalk yapmaz ve değişken tüketimli binalarda enerji tüketimi düşer. Ayrıntılar için <a href="/blog/frekans-kontrollu-kademeli-pompa-avantajlari">frekans kontrollü kademeli pompa</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
            140 => [
                'description' => ['<h2>Dikey Kademeli Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Dikey Kademeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Dikey kademeli pompa</strong> (dik milli kademeli pompa), çarkları dikey bir mil üzerinde üst üste dizilen ve motoru üstte duran yüksek basınçlı pompadır. Taban alanı küçük olduğu için dar makine dairelerinde tercih edilir; çok katlı bina besleme, hidrofor grupları, sulama ve sanayi hatlarında kullanılır.</p>
<p>Bu kategoride Sumak, Winpo ve Pedrollo markalarının dikey kademeli pompaları yer alır.</p>
<h3>Dikey Kademeli Pompa Serileri</h3>
<ul>
<li><strong>Sumak SHM ve SHT (6, 8, 12 ve 16 serileri):</strong> Paslanmaz gövdeli dikey kademeli pompalar; 1–8.5 HP. SHM modelleri 220 V monofaze, SHT modelleri 380 V trifazedir.</li>
<li><strong>Sumak SHT 24, 32, 34, 40, 50 ve 65:</strong> 5.5 HP ile 55 kW arasında trifaze modeller; 125 m³/h'e kadar debi ve 200 mss'ye kadar basma.</li>
<li><strong>Sumak SHTP ve SHTPD:</strong> Paslanmaz dikey kademeli pompalar; 2.2–7.5 HP, 1½" giriş ve çıkış, 200 mss'ye kadar basma.</li>
<li><strong>Winpo WNP VM:</strong> 1.5–7.5 HP, 65–120 mss basma ve 4.3–22 m³/h debi. Adı M ile bitenler 220 V, T ile bitenler 380 V'tur.</li>
<li><strong>Winpo WNP CVL:</strong> Tamamı paslanmaz dikey kademeli pompalar; 2–7.5 HP, 86–165 mss basma, 6–14 m³/h debi ve 380 V.</li>
<li><strong>Pedrollo MK ve MKm:</strong> 1–3 HP, 1¼" giriş ve 1" çıkış. MK modelleri 380 V, MKm modelleri 220 V'tur.</li>
</ul>
<h3>Model Adı Nasıl Okunur?</h3>
<p>Sumak SHT 8-300/8 gibi adlarda ilk sayı seriyi, tireden sonraki sayı motor gücünü (300 = 3 HP), eğik çizgiden sonraki sayı kademe sayısını gösterir. Winpo WNP VM 4-8T'de tireden sonraki sayı kademe sayısı, sondaki harf elektrik beslemesidir. Aynı seride kademe sayısı arttıkça basma yüksekliği ve motor gücü artar.</p>
<p>Yatay montaj için <a href="{$sk}/monoblok-yatay-kademeli">monoblok yatay kademeli pompalar</a>, büyük sanayi hatları için <a href="{$sk}/norm-tipi-yatay-kademeli">norm tipi yatay kademeli pompalar</a>, tüm seçenekler için <a href="{$sk}">kademeli pompalar</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Dikey Kademeli Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Bina yüksekliğine, boru kayıplarına ve en üst katta istenen basınca göre hesaplanır. Ürün adındaki mss değeri debinin sıfır olduğu noktadaki en yüksek basmadır; çalışma noktası bunun altında kalır.</li>
<li><strong>Debi:</strong> Aynı anda su kullanan daire ve musluk sayısına göre seçilir. Debiyi seri belirler; kademe sayısı basmayı değiştirir.</li>
<li><strong>Emiş koşulu:</strong> Pompanın depodan pozitif beslenmesi, yani su seviyesinin pompa girişinden yukarıda olması en sorunsuz kurulumdur. Pompa sudan yukarıdaysa ürün sayfasındaki emiş değeri aşılmamalıdır; Winpo modellerinde bu değer 7 m'dir.</li>
<li><strong>Kuru çalışma koruması:</strong> Depo boşaldığında pompayı durduran şamandıra, seviye rölesi veya basınç sensörü kullanılmalıdır.</li>
<li><strong>Elektrik:</strong> 220 V monofaze modeller küçük güçlerle sınırlıdır; büyük modeller 380 V trifazedir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/dikey-kademeli-pompa-rehberi">dikey kademeli pompa rehberi</a> ve <a href="/blog/hidrofor-grubu-secim-rehberi">hidrofor grubu seçim rehberi</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Dikey kademeli pompa kaç katlı binaya uygun?', [
                    ['q' => 'Dikey kademeli pompa kaç katlı binaya uygun?', 'a' => 'Belirleyici olan kat sayısı değil gereken basma yüksekliğidir. Kat başına yaklaşık 3 metre alınır; 10 katlı bir binada en üst katta 2 bar için sürtünme kayıplarıyla birlikte 55–60 metre civarında basma gerekir. Bu kategoride 200 mss\'ye kadar basma sağlayan modeller vardır.'],
                    ['q' => 'Model adındaki sayılar ne anlama gelir?', 'a' => 'Sumak SHT 8-300/8 örneğinde 8 seriyi, 300 motor gücünü (3 HP), /8 kademe sayısını gösterir. Winpo WNP VM 4-8T\'de 8 kademe sayısıdır, T ise 380 V anlamına gelir. Pedrollo MK 5/8\'de eğik çizgiden sonraki sayı kademe sayısıdır.'],
                    ['q' => 'Dikey mi, yatay kademeli pompa mı seçilmeli?', 'a' => 'Makine dairesi darsa dikey kademeli pompa az yer kaplar. Yatay boru hattı ve kolay servis erişimi önemliyse yatay kademeli pompa daha pratiktir. Bkz. <a href="/kategoriler/su-pompalari/kademeli-pompalar/yatay-kademeli-pompalar">yatay kademeli pompalar</a>.'],
                    ['q' => 'Dikey kademeli pompa neden ses ve titreşim yapar?', 'a' => 'Başlıca nedenler kavitasyon (yetersiz emiş basıncı), hatta hava kalması, pompanın çalışma aralığı dışında çalışması ve rulman aşınmasıdır. Ayrıntılar için <a href="/blog/kademeli-pompa-ses-titresim-ariza-teshis">kademeli pompada ses ve titreşim</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Dikey kademeli pompa kuru çalışırsa ne olur?', 'a' => 'Mekanik salmastra pompalanan suyla soğur; susuz çalışmada kısa sürede zarar görür ve pompa sızdırmaya başlar. Depo beslemeli sistemlerde şamandıra, seviye rölesi veya kuru çalışma koruması kullanılmalıdır.'],
                ]],
            ],
            146 => [
                'meta_description' => ['Konut, hidrofor ve proses hatları için monoblok', 'Sumak SYM, SYMT ve paslanmaz SYMP, SYMTP monoblok yatay kademeli pompalar: 220 V ve 380 V modeller, villa, konut ve küçük tesis basınçlandırması için.'],
                'description' => ['<h2>Monoblok Yatay Kademeli Pompa Modelleri</h2>', <<<HTML
<h2>Monoblok Yatay Kademeli Pompa Modelleri</h2>
<p><strong>Monoblok yatay kademeli pompa</strong>, motor ile pompanın tek gövdede birleştiği ve yatay mil üzerinde birden fazla çarkla basınç üreten pompadır. Kaplin ve şase gerektirmediği için kompakttır; villa, müstakil ev, küçük apartman ve işyeri basınçlandırması ile bahçe sulamasında kullanılır.</p>
<p>Bu kategorideki modellerin tamamı Sumak markalıdır.</p>
<h3>Monoblok Yatay Kademeli Pompa Serileri</h3>
<ul>
<li><strong>Sumak SYM ve SYMT (6, 8 ve 12 serileri):</strong> 1–4 HP, 1" ve 1¼" bağlantılı yatay milli kademeli pompalar. SYM modelleri 220 V monofaze, SYMT modelleri 380 V trifazedir.</li>
<li><strong>Sumak SYMP ve SYMTP:</strong> Gövde, çark ve mili AISI 304 paslanmaz yatay kademeli pompalar; 0.55–1.35 kW, 2.5–6 m³/h debi, IP54 koruma ve 0–45 °C sıvı sıcaklığı. SYMP modelleri 220 V, SYMTP modelleri 380 V'tur.</li>
<li><strong>Sumak SMJK ve SMJKT:</strong> Döküm gövdeli sessiz jet pompalar; 1–1.5 HP ve 6 m emiş.</li>
<li><strong>Sumak SMINOX/150-4:</strong> Paslanmaz gövdeli çok kademeli pompa; 1.5 HP.</li>
</ul>
<p>Sumak SYM 8-300/8 gibi adlarda tireden sonraki sayı motor gücünü (300 = 3 HP), eğik çizgiden sonraki sayı kademe sayısını gösterir.</p>
<p>Dar alanlar için <a href="{$sk}/dikey-kademeli-pompalar">dikey kademeli pompalar</a>, büyük sanayi hatları için <a href="{$sk}/norm-tipi-yatay-kademeli">norm tipi yatay kademeli pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Monoblok Yatay Kademeli Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Kot farkı, boru kayıpları ve kullanım noktasında istenen basınç toplanır. Villa ve küçük binalarda genellikle SYMP ve SYMTP, daha yüksek basma için SYM ve SYMT serisi uygundur.</li>
<li><strong>Debi:</strong> SYMP ve SYMTP modellerinde debi 2.5–6 m³/h arasındadır; daha fazla daire veya kullanım noktası için ürün sayfalarındaki debi aralıkları karşılaştırılmalıdır.</li>
<li><strong>Malzeme:</strong> İçme suyu ve paslanma istenmeyen tesisatlarda paslanmaz SYMP ve SYMTP modeller tercih edilir.</li>
<li><strong>Sıcaklık:</strong> SYMP ve SYMTP modellerinde sıvı sıcaklığı 0–45 °C'dir; sıcak su hatlarında kullanılmamalıdır.</li>
<li><strong>Hidrofor kullanımı:</strong> Basınç tankı ve basınç şalteri veya elektronik kontrol ünitesiyle birlikte kurulduğunda hidrofor olarak çalışır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/monoblok-yatay-kademeli-pompa-secimi">monoblok yatay kademeli pompa seçimi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Monoblok yatay kademeli pompa ne avantaj sağlar?', [
                    ['q' => 'Monoblok yatay kademeli pompa ne avantaj sağlar?', 'a' => 'Motor ve pompa aynı gövdede olduğu için kaplin ayarı ve ayrı şase gerekmez. Kurulumu kısa sürer, az yer kaplar ve villa, konut gibi küçük uygulamalar için pratiktir.'],
                    ['q' => 'SYMP ile SYM serisi arasındaki fark nedir?', 'a' => 'SYMP ve SYMTP modellerinde gövde, çark ve mil AISI 304 paslanmazdır; içme suyu ve paslanma istenmeyen tesisatlar için tercih edilir. SYM ve SYMT serisi 4 HP\'ye kadar motor gücü ve daha yüksek basma seçenekleri sunar.'],
                    ['q' => 'Monoblok yatay kademeli pompa hidrofor olarak kullanılabilir mi?', 'a' => 'Evet. Basınç tankı ve basınç şalteri veya elektronik kontrol ünitesiyle birlikte kurulduğunda hidrofor olarak çalışır. Hazır paket sistem isteniyorsa <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisine bakabilirsiniz.'],
                    ['q' => 'Bu pompalar sıcak su basabilir mi?', 'a' => 'SYMP ve SYMTP modellerinde sıvı sıcaklığı 0–45 °C ile sınırlıdır. Sıcak su hatları için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/sicak-su-pompalari">sıcak su pompaları</a> kategorisine bakabilirsiniz.'],
                    ['q' => 'Kademeli pompa kuru çalışırsa ne olur?', 'a' => 'Mekanik salmastra pompalanan suyla soğur; susuz çalışmada kısa sürede zarar görür ve pompa sızdırmaya başlar. Depo beslemeli sistemlerde şamandıra veya kuru çalışma koruması kullanılmalıdır.'],
                ]],
            ],
            157 => [
                'meta_description' => ['Sanayi, kazan besleme ve yüksek basınç prosesleri için norm tipi', 'Sumak SYT motorlu aküple norm tipi yatay kademeli pompalar: 4–55 kW, 380 V, PN16 flanş, GG 25 döküm gövde ve 85 °C\'ye kadar su sıcaklığı.'],
                'description' => ['<h2>Norm Tipi Yatay Kademeli Pompa Modelleri</h2>', <<<HTML
<h2>Norm Tipi Yatay Kademeli Pompa Modelleri</h2>
<p><strong>Norm tipi yatay kademeli pompa</strong>, yatay milli çok kademeli pompanın ayrı bir elektrik motoruna kaplinle bağlanıp ortak şase üzerine monte edildiği (aküple) pompadır. Motor ayrı olduğu için motor arızasında pompa gövdesi sökülmeden motor değiştirilebilir. Sanayi tesislerinde, büyük bina basınçlandırmasında ve yüksek basınçlı su transferinde kullanılır.</p>
<p>Bu kategorideki modellerin tamamı Sumak SYT serisidir.</p>
<h3>Sumak SYT Serileri</h3>
<ul>
<li><strong>SYT 32:</strong> 4–18.5 kW, 2–12 kademe.</li>
<li><strong>SYT 40:</strong> 7.5–55 kW, 2–12 kademe.</li>
<li><strong>SYT 50:</strong> 15–55 kW, 2–8 kademe; DN65 giriş ve DN50 çıkış flanşı.</li>
<li><strong>SYT 65:</strong> 37–55 kW, 2–3 kademe; DN80 giriş ve DN65 çıkış flanşı.</li>
</ul>
<p>SYT 40/5 gibi adlarda eğik çizgiden sonraki sayı kademe sayısını gösterir. Tüm modellerde gövde ve çark GG 25 döküm, mil X20Cr13 paslanmaz çeliktir. Flanşlar PN16, sıvı sıcaklığı 0–85 °C'dir; ürün verilerinde 110 °C'ye kadar seçenek belirtilir. Sızdırmazlık yumuşak salmastra veya mekanik salmastrayla sağlanır. Tüm modeller 380 V trifazedir.</p>
<p>Daha küçük uygulamalar için <a href="{$sk}/monoblok-yatay-kademeli">monoblok yatay kademeli pompalar</a>, yer tasarrufu için <a href="{$sk}/dikey-kademeli-pompalar">dikey kademeli pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Norm Tipi Kademeli Pompa Seçerken Nelere Dikkat Edilmeli?</h3>
<ul>
<li><strong>Çalışma noktası:</strong> Gereken debi ve basma yüksekliği belirlenmeli, model pompa eğrisi üzerinden seçilmelidir. Bu değerleri bize iletirseniz uygun SYT modelini birlikte belirleyebiliriz.</li>
<li><strong>Kaplin hizası:</strong> Motor ve pompa milleri aynı eksende olmalıdır. Hizası bozuk kaplin rulman, salmastra ve kaplini hızla aşındırır; ilk montajda ve periyodik bakımlarda kontrol edilmelidir.</li>
<li><strong>Temel ve borulama:</strong> Şase sağlam bir beton kaideye sabitlenmeli, boruların ağırlığı pompa flanşlarına yüklenmemelidir.</li>
<li><strong>Sızdırmazlık:</strong> Yumuşak salmastralı pompalar soğuma için az miktarda damlatarak çalışır. Sızıntı istenmeyen yerlerde mekanik salmastralı model tercih edilir.</li>
<li><strong>Yol verme:</strong> Büyük güçlerde motor yıldız-üçgen, yumuşak yol verici veya frekans invertörüyle çalıştırılır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/norm-tipi-yatay-kademeli-pompa-rehberi">norm tipi yatay kademeli pompa rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Norm tipi pompa ne demek?', [
                    ['q' => 'Norm tipi pompa ne demek?', 'a' => 'Piyasada norm tipi, pompanın ayrı bir elektrik motoruna kaplinle bağlandığı ve ortak şase üzerinde satıldığı yapıyı ifade eder. Monoblok pompadan farkı, motorun pompa gövdesinden bağımsız olmasıdır.'],
                    ['q' => 'Motorlu aküple ne anlama gelir?', 'a' => 'Pompa, elektrik motoru, kaplin ve şasenin fabrikada birleştirilip hizalanmış hâlde teslim edildiği anlamına gelir. Sumak SYT modelleri motorlu aküple olarak sunulur.'],
                    ['q' => 'Kaplin ayarı neden önemlidir?', 'a' => 'Motor ve pompa milleri aynı eksende değilse rulman, salmastra ve kaplin hızla aşınır, titreşim artar. Nakliye ve montaj sırasında hiza bozulabileceği için ilk çalıştırmadan önce kaplin hizası kontrol edilmelidir.'],
                    ['q' => 'Norm tipi kademeli pompa sürekli çalışmaya uygun mu?', 'a' => 'Çalışma noktasına göre doğru seçilmiş ve düzenli bakımı yapılan pompa uzun süreli çalışmaya uygundur. Rulman, salmastra, kaplin ve titreşim kontrolleri periyodik olarak yapılmalıdır.'],
                    ['q' => 'Motor arızalanırsa ne yapılır?', 'a' => 'Motor pompadan ayrı olduğu için pompa gövdesi sökülmeden yalnızca motor değiştirilebilir. Yeni motorun güç, devir, mil ve bağlantı ölçüleri mevcut motorla aynı olmalıdır.'],
                ]],
            ],
            163 => [
                'meta_description' => ['Bina, RO ve proses hatları için yatay kademeli pompalar.', 'Pedrollo 3CR, 4CR, 5CR, Kaysu HMC ve Winpo WNP SH yatay kademeli pompalar: 40–82 mss basma, 220 V ve 380 V modeller, ev ve bahçe basınçlandırması için.'],
                'description' => ['<h2>Yatay Kademeli Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Yatay Kademeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Yatay kademeli pompa</strong>, çarkları yatay bir mil üzerinde sıralanan ve tek fanlı pompaya göre daha yüksek basınç veren kompakt pompadır. Ev ve bahçe tesisatlarında basınçlandırma, sulama ve küçük hidrofor uygulamalarında kullanılır.</p>
<p>Bu kategoride Pedrollo, Kaysu ve Winpo markalarının yatay kademeli pompaları yer alır.</p>
<h3>Yatay Kademeli Pompa Modelleri</h3>
<ul>
<li><strong>Pedrollo 3CR, 4CR ve 5CR (N serisi):</strong> Paslanmaz gövdeli yatay kademeli pompalar; 0.6–1.5 HP, 40–67 mss basma, 4.8–7.8 m³/h debi, 1" bağlantı ve 7 m emiş. Adında "m" bulunan modeller 220 V monofazedir.</li>
<li><strong>Kaysu HMC145-6SH:</strong> Yatay milli çok kademeli temiz su pompası; 2.5 HP, 76 m basma ve 8.7 m³/h'e kadar debi.</li>
<li><strong>Winpo WNP 90-5 SH ve 90-6 SH:</strong> Çok kademeli santrifüj pompalar; 1.3–2 HP, 62–82 mss basma, 5.4 m³/h debi, 220 V ve 7 m emiş.</li>
</ul>
<p>Pedrollo modellerinde CR'den önceki sayı kademe sayısını gösterir; kademe arttıkça basma yüksekliği artar.</p>
<p>Kompakt monoblok seçenekler için <a href="{$sk}/monoblok-yatay-kademeli">monoblok yatay kademeli pompalar</a>, sanayi hatları için <a href="{$sk}/norm-tipi-yatay-kademeli">norm tipi yatay kademeli pompalar</a>, dar alanlar için <a href="{$sk}/dikey-kademeli-pompalar">dikey kademeli pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Yatay Kademeli Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Kot farkı, boru kayıpları ve kullanım noktasında istenen basınç toplanır. Ürün adındaki mss değeri debinin sıfır olduğu noktadaki en yüksek basmadır.</li>
<li><strong>Debi:</strong> Aynı anda kullanılan musluk ve sulama başlığı sayısına göre seçilir; bu kategorideki modellerde 4.8–8.7 m³/h arasındadır.</li>
<li><strong>Emiş:</strong> Pompa sudan yukarıdaysa su seviyesi ile pompa arasındaki dikey mesafe ürün sayfasındaki emiş değerini aşmamalıdır; Pedrollo ve Winpo modellerinde bu değer 7 m'dir.</li>
<li><strong>Hidrofor kullanımı:</strong> Basınç tankı ve basınç şalteri veya elektronik kontrol ünitesiyle birlikte hidrofor olarak çalışır.</li>
<li><strong>Kuru çalışma:</strong> Su kesilme riski olan depo ve kuyularda şamandıra veya kuru çalışma koruması kullanılmalıdır.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/kademeli-pompa-ne-zaman-tercih-edilir">kademeli pompa ne zaman tercih edilir</a> ve <a href="/blog/kademeli-pompa-calismiyor-ariza-bakim-rehberi">kademeli pompa arıza ve bakım rehberi</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Yatay kademeli pompa neden kullanılır?', [
                    ['q' => 'Yatay kademeli pompa neden kullanılır?', 'a' => 'Tek fanlı pompanın basıncı yetmediğinde birden fazla çark seri çalıştırılarak daha yüksek basınç elde edilir. Yatay yapı boru hattına kolay bağlanır ve servis erişimini kolaylaştırır.'],
                    ['q' => 'Pedrollo 3CR, 4CR ve 5CR arasındaki fark nedir?', 'a' => 'CR\'den önceki sayı kademe sayısını gösterir. Kademe arttıkça basma yükselir: 3CR 80 modelinde 40 mss, 4CRm 80 modelinde 52 mss, 5CR 80 modelinde 67 mss. Adında "m" bulunan modeller 220 V, bulunmayanlar 380 V\'tur.'],
                    ['q' => 'Yatay kademeli pompa hidrofor olarak kullanılabilir mi?', 'a' => 'Evet. Basınç tankı ve basınç şalteri veya elektronik kontrol ünitesiyle birlikte kurulduğunda hidrofor olarak çalışır. Hazır paket sistem için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> kategorisine bakabilirsiniz.'],
                    ['q' => 'Dikey mi, yatay kademeli pompa mı seçilmeli?', 'a' => 'Alan darsa dikey kademeli pompa, yatay boru hattı ve kolay servis erişimi önemliyse yatay kademeli pompa daha uygundur. Yüksek debi ve 100 mss üzerindeki basmalar için <a href="/kategoriler/su-pompalari/kademeli-pompalar/dikey-kademeli-pompalar">dikey kademeli pompalara</a> bakabilirsiniz.'],
                    ['q' => 'Kademeli pompa ses yapar mı?', 'a' => 'Doğru seçilip doğru kurulduğunda normal çalışma sesi dışında ses yapmaz. Kavitasyon, hatta hava kalması, rulman aşınması veya pompanın çalışma aralığı dışında çalışması ses ve titreşime yol açar. Ayrıntılar için <a href="/blog/kademeli-pompa-ses-titresim-ariza-teshis">kademeli pompada ses ve titreşim</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
        ];
    }
};
