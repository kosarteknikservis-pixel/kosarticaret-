<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SP = '/kategoriler/su-pompalari';

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
        $hs = self::HS;

        return [
            121 => [
                'meta_description' => ['Bahçe sulaması ve konut su tesisatı için preferikal', 'Preferikal (sürtme fanlı) pompalar: Pedrollo PKm, PQm, PVm ve CKm, Sumak SM, Winpo WNP ve Kaysu HQBm. Düşük debide yüksek basınç, 220 V ve 380 V.'],
                'description' => ['<h2>Preferikal Pompa (Sürtme Fanlı) Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Preferikal Pompa (Sürtme Fanlı) Modelleri ve Fiyatları</h2>
<p><strong>Preferikal pompa</strong> (sürtme fanlı pompa), çarkın çevresindeki kanatçıkların gövdedeki dar kanalda suyu defalarca hızlandırmasıyla çalışan yüzey tipi su pompasıdır. Küçük gövdesine göre yüksek basınç verir; debisi ise düşüktür. Kataloğumuzdaki modellerin debisi saatte 5 m³'ün altında, basma yüksekliği 20–92 metre arasındadır.</p>
<p>Evde basınç artırma, küçük hidrofor sistemleri, damla sulama ve depodan su aktarma gibi az su ama yüksek basınç gereken işlerde kullanılır. Bu kategoride Pedrollo, Sumak, Winpo ve Kaysu markalarının preferikal pompaları yer alır.</p>
<h3>Preferikal Pompa Modelleri</h3>
<ul>
<li><strong>Pedrollo PKm ve PK:</strong> 0.5–1 HP, 40–90 mss basma ve saatte 2.4–3 m³ debi. PKm modelleri 220 V, PK modelleri 380 V'tur.</li>
<li><strong>Pedrollo PQm ve PQ:</strong> Döküm gövdeli PQm 60 ve 65 ile bronz gövdeli PQm 60-BS, 65-BS, 81-BS ve PQ 81-BS modelleri; 0.5–0.75 HP ve 90 mss'ye kadar basma.</li>
<li><strong>Pedrollo PQAm ve PQA:</strong> Yandan emişli preferikal pompalar; 0.5–0.75 HP, 220 V ve 380 V.</li>
<li><strong>Pedrollo PVm ve PV:</strong> Bronz gövdeli preferikal pompalar; 0.25–0.5 HP ve 85 mss'ye kadar basma.</li>
<li><strong>Pedrollo CKm ve CK:</strong> Mazot, yakıt ve yağ transferi için preferikal pompalar; 0.33–1 HP ve 20–51 mss basma.</li>
<li><strong>Sumak SM ve SMT:</strong> 0.5, 1 ve 1.5 HP; 1 inç giriş-çıkış ve 6 m emiş. SM modelleri 220 V, SMT modelleri 380 V'tur.</li>
<li><strong>Winpo WNP 60, 70 ve 80:</strong> 0.5–1 HP, 1 inç giriş-çıkış, 6 m emiş ve saatte 1.8–3.6 m³ debi. WNP 60'ın 380 V versiyonu da bulunur.</li>
<li><strong>Kaysu HQBm60 ve HQBm80:</strong> Bronz çarklı, 220 V preferikal pompalar; 0.5 ve 1 HP.</li>
</ul>
<p>Daha fazla su gereken işler için <a href="{$sp}/santrifuj-pompalar">santrifüj pompalar</a>, hem yüksek debi hem yüksek basınç için <a href="{$sp}/kademeli-pompalar">kademeli pompalar</a>, su seviyesi pompanın altında kalan kuyular için <a href="{$sp}/jet-pompalar-derinden-emisli">jet pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Preferikal Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Pompa ile en yüksek kullanım noktası arasındaki dikey mesafeye boru kayıpları ve musluklarda istenen basınç eklenir. 1 bar yaklaşık 10 metre su sütunudur.</li>
<li><strong>Debi:</strong> Aynı anda açık kalacak musluk veya damlatıcı sayısına göre hesaplanır. İhtiyaç saatte 5 m³'ü aşıyorsa santrifüj veya kademeli pompa daha uygundur.</li>
<li><strong>Emiş derinliği:</strong> Ürün sayfalarında modele göre 6–9 metre verilir. Su seviyesi daha derindeyse jet pompa veya dalgıç pompa gerekir.</li>
<li><strong>Su kalitesi:</strong> Çark ile gövde arasındaki boşluk dar olduğu için yalnızca temiz su basılmalıdır; kum ve tortu çarkı aşındırır.</li>
<li><strong>Gövde malzemesi:</strong> Kireçli veya uzun süre bekleyen sularda bronz gövdeli ya da bronz çarklı modeller tercih edilebilir.</li>
<li><strong>Elektrik:</strong> Evlerde 220 V (monofaze), üç fazlı hattı olan işletmelerde 380 V (trifaze) model seçilir.</li>
</ul>
<p>Otomatik çalışma için pompa basınç tankı ve basınç şalteriyle birlikte <a href="{$hs}/ev-tipi-hidroforlar">ev tipi hidrofor</a> olarak ya da <a href="{$hs}/hidromat">hidromat</a> ile kullanılabilir. Ayrıntılı anlatım için <a href="/blog/preferikal-pompa-nedir-surtme-fanli">preferikal pompa nedir</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Preferikal pompa ne anlama gelir?', [
                    ['q' => 'Preferikal pompa ne anlama gelir?', 'a' => 'Adı İngilizce "peripheral" (çevresel) kelimesinden gelir; sürtme fanlı pompa olarak da bilinir. Çarkın çevresindeki kanatçıklar suyu gövdedeki dar kanalda defalarca hızlandırır; bu sayede küçük bir pompa yüksek basınç verir.'],
                    ['q' => 'Preferikal pompa ile santrifüj pompa arasındaki fark nedir?', 'a' => 'Preferikal pompa az su ama yüksek basınç verir; kataloğumuzdaki modellerde debi saatte 5 m³\'ün altındadır. Santrifüj pompa ise aynı güçte çok daha fazla su basar, basıncı daha düşüktür. Bahçeyi bol suyla sulamak için santrifüj, evde basınç artırmak için preferikal tercih edilir.'],
                    ['q' => 'Preferikal pompa kaç metreden su çeker?', 'a' => 'Ürün sayfalarında emiş derinliği modele göre 6–9 metre olarak verilir; boru kayıpları bu değeri pratikte düşürür. Su seviyesi daha derindeyse <a href="/kategoriler/su-pompalari/jet-pompalar-derinden-emisli">jet pompa</a> veya dalgıç pompa gerekir.'],
                    ['q' => 'Preferikal pompa kumlu suda kullanılır mı?', 'a' => 'Önerilmez. Çark ile gövde arasındaki boşluk dar olduğu için kum ve tortu çarkı hızla aşındırır, pompanın basıncı düşer. Emiş hattına filtre takılmalı ve yalnızca temiz su basılmalıdır.'],
                    ['q' => 'Preferikal pompa ile hidrofor kurulabilir mi?', 'a' => 'Evet. Pompa basınç tankı ve basınç şalteriyle birlikte kullanıldığında basınç düştüğünde çalışır, yeterli basınca ulaşınca durur. Hazır paketler için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a> sayfasına bakabilirsiniz.'],
                ]],
            ],
            122 => [
                'description' => ['<h2>Jet Pompa (Derinden Emişli) Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Jet Pompa (Derinden Emişli) Modelleri ve Fiyatları</h2>
<p><strong>Jet pompa</strong>, gövdesindeki ejektör sayesinde suyu normal bir yüzey pompasından daha derinden emebilen, kendinden emişli yüzey tipi su pompasıdır. Pompa kuyunun veya deponun yanına kurulur; su seviyesi pompanın altında kalan sığ kuyu, sarnıç ve depolarda kullanılır.</p>
<p>Bu kategorideki modellerin ürün sayfalarında emiş derinliği 6–9 metre olarak verilir. Su seviyesi daha derinde olan sondaj kuyuları için <a href="{$sp}/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a> gerekir.</p>
<p>Bu kategoride Pedrollo, Sumak, Winpo ve Kaysu markalarının jet pompaları yer alır.</p>
<h3>Jet Pompa Modelleri</h3>
<ul>
<li><strong>Pedrollo JSWm (döküm gövde):</strong> JSWm 2CX-N (1 HP, 50 mss) ve 2AX-N (1.5 HP, 58 mss); saatte 4.2 m³ debi ve 9 m emiş.</li>
<li><strong>Pedrollo JCRm (paslanmaz gövde):</strong> JCRm 1A (0.75 HP, 48 mss), 2C (1 HP, 50 mss) ve 2A (1.5 HP, 60 mss); 9 m emiş.</li>
<li><strong>Pedrollo 3CR, 4CR ve 5CR (paslanmaz gövde, kademeli):</strong> 0.6–1.5 HP, 40–67 mss basma, saatte 4.8–7.8 m³ debi ve 7 m emiş. "m" harfli modeller 220 V, diğerleri 380 V'tur.</li>
<li><strong>Pedrollo 4CPm 80-C ve 100-C (döküm gövde):</strong> Sessiz çalışan 0.75 ve 1 HP modeller; 7 m emiş.</li>
<li><strong>Sumak SMJ ve SMJT:</strong> 0.85, 1, 1.5 ve 2.2 HP kendinden emişli jet pompalar. SMJ modelleri 220 V, SMJT modelleri 380 V'tur. SMJK 100 ve SMJKT 100 modelleri de bu gruptadır.</li>
<li><strong>Winpo WNP 100M, 150M ve 100P:</strong> Döküm gövdeli 1 ve 1.5 HP ile paslanmaz gövdeli 1 HP modeller; 43–52 mss basma.</li>
<li><strong>Kaysu HKJM100 ve HKJM150:</strong> 1 ve 1.5 HP, 220 V, 9 metreye kadar emiş.</li>
</ul>
<h3>Jet Pompa Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca motor gücü, gövde malzemesi (döküm veya paslanmaz), kademe sayısı, elektrik beslemesi ve markası belirler.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Jet Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Su seviyesi:</strong> Pompa ile kuyudaki en düşük su seviyesi arasındaki dikey mesafe, pompanın emiş derinliğini aşmamalıdır. Emiş hattının uzunluğu ve dirsekler bu değeri pratikte düşürür.</li>
<li><strong>Basma yüksekliği:</strong> Pompa ile en yüksek kullanım noktası arasındaki dikey mesafeye boru kayıpları ve musluklarda istenen basınç eklenir. 1 bar yaklaşık 10 metre su sütunudur.</li>
<li><strong>Debi:</strong> Aynı anda kullanılacak musluk, duş veya sulama hattı sayısına göre belirlenir. Kuyunun verebileceği sudan fazla debi seçilmemelidir.</li>
<li><strong>Gövde malzemesi:</strong> Kireçli veya aşındırıcı sularda paslanmaz gövdeli modeller (Pedrollo JCRm ve CR serileri, Winpo WNP 100P) tercih edilebilir.</li>
<li><strong>Emme hattı:</strong> Emme borusunun ucuna dip klapesi (çekvalf) takılmalı, bağlantılar hava almamalıdır. Boru çapı pompanın giriş çapından küçük olmamalıdır.</li>
<li><strong>Elektrik:</strong> Evlerde 220 V (monofaze), üç fazlı hattı olan işletmelerde 380 V (trifaze) model seçilir.</li>
</ul>
<p>Otomatik çalışma için jet pompa basınç tankı ve basınç şalteriyle birlikte <a href="{$hs}/ev-tipi-hidroforlar">ev tipi hidrofor</a> olarak kullanılabilir. Ayrıntılı anlatım için <a href="/blog/jet-pompa-secim-rehberi-kapsamli">jet pompa seçim rehberi</a>, <a href="/blog/jet-pompa-mi-dalgic-pompa-mi-farki">jet pompa mı dalgıç pompa mı</a> ve <a href="/blog/jet-pompa-emme-priming-rehberi">jet pompada emme ve ilk doldurma</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Jet pompa ile dalgıç pompa arasında hangisini seçmeliyim?', [
                    ['q' => 'Jet pompa ile dalgıç pompa arasında hangisini seçmeliyim?', 'a' => 'Su seviyesi pompanın emiş derinliği içindeyse (kataloğumuzdaki modellerde 6–9 metre) jet pompa yeterlidir ve bakımı yüzeyde yapıldığı için kolaydır. Su seviyesi daha derindeyse veya dar sondaj kuyusu varsa <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a> gerekir.'],
                    ['q' => 'Jet pompa neden su çekmiyor?', 'a' => 'En sık nedenler emme hattındaki hava kaçağı, dip klapesinin kaçırması, pompa gövdesinin suyla doldurulmaması ve su seviyesinin emiş sınırının altına düşmesidir. Önce gövdeyi suyla doldurun, ardından emme hattı bağlantılarını ve dip klapesini kontrol edin.'],
                    ['q' => 'Jet pompa ilk çalıştırmada nasıl doldurulur?', 'a' => 'Pompa gövdesindeki doldurma tapası açılır, gövde ve emme hattı suyla tamamen doldurulur, tapa kapatılır ve pompa çalıştırılır. Pompa susuz çalıştırılmamalıdır; mekanik salmastra zarar görür.'],
                    ['q' => 'Jet pompa basınç tankı olmadan çalışır mı?', 'a' => 'Çalışır, ancak her musluk açılışında pompa devreye girer ve sık dur-kalk motoru yorar. Evde otomatik kullanım için pompa basınç tankı ve basınç şalteriyle birlikte, hidrofor olarak kurulmalıdır.'],
                    ['q' => 'Jet pompa ile bahçe sulaması yapılır mı?', 'a' => 'Evet; sığ kuyu, sarnıç veya depodan bahçe sulamak için kullanılır. Çok sayıda yağmurlama başlığı aynı anda çalışacaksa debi yetmeyebilir; bu durumda <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a> daha uygundur.'],
                ]],
            ],
        ];
    }
};
