<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SD = '/kategoriler/su-pompalari/dalgic-pompalar';

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
        $sd = self::SD;

        return [
            131 => [
                'description' => ['<h2>Temiz Su Dalgıç Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Temiz Su Dalgıç Pompası Modelleri ve Fiyatları</h2>
<p><strong>Temiz su dalgıç pompası</strong>, su deposu, sarnıç, keson kuyu veya havuz gibi kaynakların içine daldırılarak temiz ya da az tortulu suyu basan elektrikli pompadır. Pompa suyun içinde çalıştığı için yüzey pompalarındaki emiş sorunu yaşanmaz.</p>
<p>Bu kategoride Pedrollo, Sumak, Winpo ve Kaysu markalarının temiz su dalgıç pompaları yer alır. Modeller iki gruba ayrılır: suyu boşaltmak veya kısa mesafeye aktarmak için tek kademeli pompalar ve suyu binaya ya da yükseğe basmak için daha yüksek basma veren pompalar.</p>
<h3>Boşaltma ve Transfer İçin Modeller</h3>
<ul>
<li><strong>Pedrollo TOP:</strong> Flatörlü, plastik gövdeli pompalar; TOP 1'den TOP 5'e kadar 0.33–1.25 HP. GM modelleri gizli flatörlü, VORTEX modelleri 20 mm'ye kadar parça geçirir. Sıfırdan emişli TOP2 FLOOR, zemindeki suyu çok düşük seviyeye kadar çeker.</li>
<li><strong>Pedrollo RXm ve RX:</strong> Paslanmaz gövdeli, flatörlü veya gizli flatörlü (GM) drenaj pompaları; 0.33–1.5 HP. Ayrıntılar için <a href="{$sd}/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj dalgıç pompalar</a> sayfasına bakabilirsiniz.</li>
<li><strong>Sumak SDF 300, 5, 10, 13 ve 13A:</strong> 0.33–1.3 HP ve 13.5 m'ye kadar basma. SDF 13A asansör flatörlüdür. SDF500 GF ise sıfırdan emişli ve gizli şamandıralıdır.</li>
<li><strong>Winpo QDP ve WNP A:</strong> QDP 400/550/750 A ve WNP 400/550/750 A flatörlü drenaj pompaları; 0.5–1 HP. GF modelleri gizli flatörlü, WNP 750 PD paslanmaz gövdelidir.</li>
<li><strong>Kaysu SP ve SPAUTO:</strong> SP400-A ve SP750-A ile sensörlü SPAUTO400-A ve gizli flatörlü SPAUTO750-A; 0.5 ve 1 HP.</li>
</ul>
<h3>Binaya ve Yükseğe Su Basan Modeller</h3>
<ul>
<li><strong>Pedrollo TOP MULTI:</strong> Çok kademeli, 0.5–0.75 HP ve 42 mss'ye kadar basma. EVO versiyonları ile otomatik basınç kontrollü TECH versiyonları bulunur.</li>
<li><strong>Sumak SDF 15/1, 5/2, 8/3, 12/3 ve 25/2:</strong> 1–3 HP ve 21–40 m basma. SDT 25/2 modeli 380 V'tur.</li>
<li><strong>Kaysu QDX:</strong> QDX1.5-16 ve QDX1.5-32 modelleri ile döküm gövdeli QDX6-32 ve 3 kademeli QDX6-39/3 (2 HP, saatte 15 m³'e kadar debi).</li>
</ul>
<p>Dar sondaj kuyuları için <a href="{$sd}/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompalar</a>, çamurlu ve iri parçalı su için <a href="{$sd}/kirli-su-dalgic-pompa">kirli su dalgıç pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Temiz Su Dalgıç Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Su seviyesi ile en yüksek kullanım noktası arasındaki dikey mesafeye boru kayıpları ve istenen basınç eklenir; 1 bar yaklaşık 10 metre su sütunudur. Boşaltma ve transfer için tek kademeli, binaya su basmak için yüksek basmalı model seçilir.</li>
<li><strong>Debi:</strong> Boşaltma işlerinde hazne hacmine ve istenen süreye, kullanım suyunda aynı anda açılacak musluk sayısına göre belirlenir.</li>
<li><strong>Su kalitesi:</strong> Çok kademeli pompalar yalnızca temiz suya uygundur; örneğin Pedrollo TOP MULTI en fazla 1.3 mm parça geçirir. Tortulu suda tek kademeli veya vortex çarklı model seçilir.</li>
<li><strong>Otomatik çalışma:</strong> Flatörlü (şamandıralı) modeller su seviyesine göre çalışıp durur. Dar haznelerde gizli flatörlü (GM, GF) veya sensörlü modeller daha az yer kaplar.</li>
<li><strong>En düşük su seviyesi:</strong> Zemindeki suyu son santimetrelerine kadar çekmek için Pedrollo TOP2 FLOOR ve Sumak SDF500 GF gibi sıfırdan emişli modeller kullanılır.</li>
<li><strong>Montaj:</strong> Pompa kablosundan değil, taşıma halatından asılmalıdır. Tortulu haznelerde pompa tabandan biraz yukarıda tutulmalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/temiz-su-dalgic-pompa-rehberi">temiz su dalgıç pompa rehberi</a> ve <a href="/blog/flatorlu-dalgic-pompa-nasil-calisir">flatörlü dalgıç pompa nasıl çalışır</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Temiz su dalgıç pompası ile normal drenaj pompası arasındaki fark nedir?', [
                    ['q' => 'Temiz su dalgıç pompası ile drenaj pompası arasındaki fark nedir?', 'a' => 'Drenaj pompası hazneyi boşaltmak için tasarlanır; basma yüksekliği düşük, debisi yüksektir ve küçük parçaları geçirir (örneğin Pedrollo TOP 10 mm). Çok kademeli temiz su pompaları ise daha yükseğe basar ama yalnızca temiz suyla çalışır. Bu kategoride iki tip de bulunur.'],
                    ['q' => 'Depo veya keson kuyudan binaya su basmak için hangi pompa gerekir?', 'a' => 'Önce basma yüksekliği hesaplanır: su seviyesi ile en üst musluk arasındaki yükseklik, boru kayıpları ve istenen basınç toplanır. Binaya su basmak için genellikle Pedrollo TOP MULTI veya Sumak SDF 5/2, 8/3, 12/3 gibi yüksek basmalı modeller seçilir. Keson kuyular için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/keson-kuyu-pompa">keson kuyu pompaları</a> sayfasına da bakabilirsiniz.'],
                    ['q' => 'Sarnıçtaki suyu basmak için dalgıç pompa mı yüzey pompası mı?', 'a' => 'Dalgıç pompa suyun içinde çalıştığı için emiş sorunu yaşamaz ve su seviyesi düşse de çalışmaya devam eder. Yüzey pompasının bakımı daha kolaydır, ancak emiş derinliği sınırlıdır ve emme hattı hava almamalıdır.'],
                    ['q' => 'Temiz su dalgıç pompası kuru çalışırsa ne olur?', 'a' => 'Motor ve mekanik salmastra pompalanan suyla soğur; susuz çalışmada kısa sürede zarar görür. Su seviyesi düşebilen yerlerde flatörlü model veya ayrı seviye kontrolü kullanılmalıdır.'],
                    ['q' => 'Dalgıç pompa sürekli suyun içinde kalabilir mi?', 'a' => 'Evet, dalgıç pompalar suyun içinde çalışacak şekilde üretilir. Uzun süre kullanılmayacaksa pompanın çıkarılıp temiz suyla durulanması ve donma riski olan yerlerde korunması önerilir.'],
                ]],
            ],
            135 => [
                'description' => ['<h2>Paslanmaz Drenaj Dalgıç Pompa Fiyatları ve Modelleri</h2>', <<<HTML
<h2>Paslanmaz Drenaj Dalgıç Pompa Fiyatları ve Modelleri</h2>
<p><strong>Paslanmaz drenaj dalgıç pompası</strong>, gövdesi paslanmaz çelikten yapılmış, bodrum, çukur, depo ve havuz gibi yerlerde biriken temiz veya az kirli suyu boşaltan dalgıç pompadır. Paslanmaz gövde plastik gövdeye göre darbelere daha dayanıklıdır ve döküm gövde gibi paslanmaz.</p>
<p>Bu kategoride Pedrollo, Sumak ve Winpo markalarının paslanmaz gövdeli drenaj pompaları yer alır.</p>
<h3>Paslanmaz Drenaj Pompası Modelleri</h3>
<ul>
<li><strong>Pedrollo RXm ve RX:</strong> Full paslanmaz drenaj pompaları; RXm 1'den RXm 5'e kadar 0.33–1.5 HP, 7.5–20 mss basma ve saatte 9.6–18 m³ debi. RXm modelleri 220 V, RX modelleri 380 V'tur.</li>
<li><strong>Pedrollo RXm /20 ve /40:</strong> 20 mm ve 40 mm'ye kadar parça geçiren versiyonlar (RXm 3/20, RXm 4/40, RXm 5/40, RX 5/40); yaprak, kum ve küçük parçalı su içindir.</li>
<li><strong>Pedrollo GM modelleri:</strong> Gizli flatörlü versiyonlar; şamandıra gövdenin içinde olduğu için dar haznelerde takılma riski azdır.</li>
<li><strong>Sumak SDF 6 ve 6A:</strong> 0.5 HP, 1¼ inç çıkışlı paslanmaz gövdeli drenaj pompaları.</li>
<li><strong>Sumak SDF 10/2, 14/2 ve 18/2:</strong> 1–1.8 HP, 2 inç çıkış ve 10.5–13.5 m basma veren paslanmaz gövdeli foseptik dalgıç pompaları.</li>
<li><strong>Winpo WNP QCK:</strong> 45M, 55M, 75M, 100M ve 150M modelleri; 0.35–1.5 HP, 8–18 mss basma, saatte 9–21 m³ debi, flatörlü ve 10 m kablolu.</li>
<li><strong>Winpo WNP 750 PD:</strong> 0.75 kW paslanmaz gövdeli drenaj pompası, 10 m kablolu.</li>
</ul>
<p>Plastik gövdeli modeller için <a href="{$sd}/drenaj-dalgic-pompa">drenaj dalgıç pompalar</a>, iri parçalı ve çamurlu su için <a href="{$sd}/kirli-su-dalgic-pompa">kirli su dalgıç pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Paslanmaz Drenaj Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Parça geçişi:</strong> Temiz ve az tortulu suda standart modeller (örneğin Pedrollo RXm, 10 mm) yeterlidir. Yaprak, kum ve küçük parça içeren suda 20 veya 40 mm geçişli modeller seçilir. Foseptik atık için <a href="{$sd}/foseptik-dalgic-pompa">foseptik dalgıç pompa</a> gerekir.</li>
<li><strong>Debi ve basma yüksekliği:</strong> Haznenin ne kadar sürede boşaltılacağı ve tahliye noktasının yüksekliği belirleyicidir. Hortum uzunluğu ve dirsekler basma kapasitesini düşürür.</li>
<li><strong>Şamandıra:</strong> Geniş haznelerde dış flatörlü, dar haznelerde gizli flatörlü (GM) modeller tercih edilir.</li>
<li><strong>Sıvı:</strong> Ürün sayfalarında sıvı sıcaklığı modele göre 35–50 °C arasında verilir. Deniz suyu, kimyasal veya aşındırıcı sıvılarda malzeme uygunluğu üreticiden teyit edilmelidir; kimyasal transferi için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal">paslanmaz pompalar</a> sayfasına bakabilirsiniz.</li>
<li><strong>Elektrik:</strong> Evlerde 220 V (monofaze), üç fazlı hattı olan işletmelerde 380 V (trifaze) model seçilir.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/paslanmaz-drenaj-dalgic-pompa-secimi">paslanmaz drenaj dalgıç pompa seçimi</a> ve <a href="/blog/drenaj-cukuru-hacmi-hesabi-tasma-payi">drenaj çukuru hacmi hesabı</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['AISI 304 ve AISI 316 paslanmaz çelik arasındaki fark nedir?', [
                    ['q' => 'Paslanmaz drenaj pompası ne zaman tercih edilir?', 'a' => 'Pompanın sık taşındığı, darbe alabileceği veya uzun süre suyun içinde kalacağı yerlerde tercih edilir. Paslanmaz gövde plastik gövdeye göre daha sağlamdır ve döküm gövde gibi paslanmaz.'],
                    ['q' => 'Paslanmaz drenaj pompasıyla havuz boşaltılır mı?', 'a' => 'Evet, havuz ve süs havuzu boşaltmada kullanılır. Pompanın dipteki yaprak ve tortuyu emip tıkanmaması için havuz önce kaba temizlenmeli, gerekirse 20 veya 40 mm parça geçişli model seçilmelidir.'],
                    ['q' => 'Deniz suyunda kullanılabilir mi?', 'a' => 'Ürün sayfalarındaki bilgiler temiz ve az kirli su içindir. Deniz suyu, kimyasal veya klor oranı yüksek sularda kullanmadan önce malzeme uygunluğu üreticiden teyit edilmelidir. Kullanımdan sonra pompayı temiz suyla durulamak ömrünü uzatır.'],
                    ['q' => 'Flatörlü ile gizli flatörlü pompa arasındaki fark nedir?', 'a' => 'Flatörlü pompada şamandıra kabloya bağlıdır; su yükselince şamandıra kalkar ve pompa çalışır, bu yüzden hareket edebileceği genişlikte hazne gerekir. Gizli flatörlü (GM) modellerde şamandıra gövdenin içindedir ve dar haznelere daha uygundur.'],
                    ['q' => 'Pedrollo RXm 4/40 ne anlama gelir?', 'a' => '"m" harfi 220 V (monofaze) modeli, sondaki /40 ise pompanın 40 mm\'ye kadar parça geçirebildiğini gösterir. RXm 3/20 gibi /20 modeller 20 mm\'ye kadar parça geçirir; eki olmayan RXm modellerinde bu değer 10 mm\'dir.'],
                ]],
            ],
            154 => [
                'meta_description' => ['Tekne ve marin uygulamalar için otomatik sintine', 'Tekne sintinesi için 12 V Sumak STNF750G otomatik flatörlü sintine pompası: saatte 1000 litre debi, 4 mss basma. Seçim ve montaj bilgileri.'],
                'description' => ['<h2>Sintine Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Sintine Pompası Modelleri ve Fiyatları</h2>
<p><strong>Sintine pompası</strong>, teknenin en alt bölümünde (sintine) biriken suyu dışarı atan küçük dalgıç pompadır. Tekne aküsüyle çalışır; otomatik modeller su seviyesi yükseldiğinde kendiliğinden devreye girer.</p>
<h3>Sumak STNF750G</h3>
<ul>
<li>12 V DC ile çalışır ve tekne aküsüne bağlanır.</li>
<li>Saatte 1000 litre debi ve 4 mss basma verir.</li>
<li>Otomatik flatörlüdür; su yükselince çalışır, su çekilince durur.</li>
</ul>
<p>Karada biriken suyu boşaltmak için <a href="{$sd}/drenaj-dalgic-pompa">drenaj dalgıç pompalar</a> ve <a href="{$sd}/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj dalgıç pompalar</a> sayfasına bakabilirsiniz. Teknede basınçlı kullanım suyu için <a href="/blog/karavan-tekne-12-24-volt-hidrofor">karavan ve tekne için 12/24 volt hidrofor</a> yazımızı okuyabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Sintine Pompası Seçerken Nelere Dikkat Edilmeli?</h3>
<ul>
<li><strong>Voltaj:</strong> Pompanın voltajı tekne elektrik sistemiyle aynı olmalıdır. 12 V pompa 24 V sisteme veya 220 V şebekeye bağlanmaz.</li>
<li><strong>Debi:</strong> Teknenin su alma riskine göre seçilir. Büyük teknelerde birden fazla pompa veya yedek pompa kullanılması önerilir.</li>
<li><strong>Montaj:</strong> Pompa sintinenin en alçak noktasına, flatörün serbestçe hareket edebileceği şekilde yerleştirilmelidir.</li>
<li><strong>Elektrik güvenliği:</strong> Kablo bağlantıları su almayacak şekilde yapılmalı, hat sigortayla korunmalıdır.</li>
<li><strong>Bakım:</strong> Emiş ızgarası düzenli olarak temizlenmeli, flatörün sıkışmadığı kontrol edilmelidir.</li>
</ul>
HTML.self::CONTACT],
                'faq' => ['Sintine pompası otomatik çalışır mı?', [
                    ['q' => 'Sintine pompası otomatik çalışır mı?', 'a' => 'Otomatik flatörlü modeller su belirli bir seviyeye çıktığında kendiliğinden çalışır ve su çekilince durur. Sumak STNF750G otomatik flatörlüdür.'],
                    ['q' => 'Sintine pompası 220 V prizle çalışır mı?', 'a' => 'Hayır. Sumak STNF750G 12 V DC ile çalışır; tekne aküsüne veya uygun bir 12 V DC güç kaynağına bağlanmalıdır.'],
                    ['q' => 'Sintine pompası tuzlu suda kullanılır mı?', 'a' => 'Sintine pompaları teknede kullanılmak için üretilir. Denizde kullanımdan sonra pompanın ve emiş ızgarasının tatlı suyla durulanması ömrünü uzatır.'],
                    ['q' => 'Yağlı sintine suyu denize boşaltılabilir mi?', 'a' => 'Hayır. Yağlı sintine suyu denize boşaltılmamalı, mevzuata uygun şekilde toplanıp bertaraf edilmelidir.'],
                    ['q' => 'Sintine pompası neden çalışmaz veya tıkanır?', 'a' => 'Saç, ip, plastik parça ve tortu emiş ızgarasını tıkayabilir; flatör kir nedeniyle sıkışabilir. Akünün zayıflaması veya bağlantılardaki oksitlenme de pompanın çalışmasını engeller. Izgara, flatör ve bağlantılar düzenli kontrol edilmelidir.'],
                ]],
            ],
            159 => [
                'meta_description' => ['Şantiye, maden ve arıtma çamuru için karıştırıcılı', 'Şantiye, hafriyat ve çökeltme havuzları için Sumak SDT 50/3 C ve SDT 75/3 C karıştırıcılı çamur dalgıç pompaları: 5.5–7.5 HP, 380 V, 3 inç çıkış.'],
                'description' => ['<h2>Karıştırıcılı Çamur Pompası Modelleri</h2>', <<<HTML
<h2>Karıştırıcılı Çamur Pompası Modelleri</h2>
<p><strong>Karıştırıcılı çamur pompası</strong>, emiş ağzının altındaki karıştırıcı sayesinde dipte çökmüş çamur ve tortuyu suyla karıştırarak pompalayan dalgıç pompadır. Standart drenaj pompalarının ememediği çökmüş tortunun bulunduğu şantiye çukurları, hafriyat alanları ve çökeltme havuzlarında kullanılır.</p>
<h3>Sumak SDT C Modelleri</h3>
<ul>
<li><strong>Sumak SDT 50/3 C:</strong> 5.5 HP, 380 V, 3 inç giriş-çıkış, saatte 76–100 m³ debi, 11–20 mss basma ve 1450 d/d.</li>
<li><strong>Sumak SDT 75/3 C:</strong> 7.5 HP (5.5 kW), 380 V, 3 inç çıkış, saatte 76–100 m³ debi, 11–20 mss basma ve 1450 d/d.</li>
</ul>
<p>Hafif kirli su için <a href="{$sd}/kirli-su-dalgic-pompa">kirli su dalgıç pompalar</a>, foseptik atık için <a href="{$sd}/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Karıştırıcılı Çamur Pompası Seçerken Nelere Dikkat Edilmeli?</h3>
<ul>
<li><strong>Elektrik:</strong> İki model de 380 V (trifaze) çalışır; şantiyede uygun kumanda panosu ve motor koruması kullanılmalıdır.</li>
<li><strong>Debi ve basma yüksekliği:</strong> Çukurun hacmi, boşaltma süresi ve tahliye noktasının yüksekliği belirleyicidir.</li>
<li><strong>Çamurun kıvamı:</strong> Çamur suyla karışabilecek kıvamda olmalıdır. Katılaşmış çamur, iri taş ve moloz pompayı zorlar.</li>
<li><strong>Yerleştirme:</strong> Pompa çamura gömülmemeli; taşıma halatıyla asılmalı veya sağlam bir zemine konmalıdır.</li>
<li><strong>Basma hattı:</strong> 3 inç çıkış daraltılmamalıdır; dar hortumda debi düşer ve çamur hatta çöker.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/kirli-su-dalgic-pompa-nedir-secim">kirli su dalgıç pompa nedir</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Karıştırıcılı çamur pompası ne fark eder?', [
                    ['q' => 'Karıştırıcılı çamur pompası ne fark eder?', 'a' => 'Emiş ağzının altındaki karıştırıcı, dipte çökmüş tortuyu kaldırıp suyla karıştırır. Böylece standart drenaj pompasının ememediği çökmüş çamur pompalanabilir hale gelir.'],
                    ['q' => 'Kumlu ve çamurlu suda çalışır mı?', 'a' => 'Evet, bu iş için tasarlanmıştır. Ancak iri taş ve moloz pompayı zorlar; aşındırıcı kum da çark ve salmastrayı daha hızlı yıpratır, bu yüzden bakım aralığı temiz su pompalarına göre kısadır.'],
                    ['q' => 'Karıştırıcılı çamur pompası 220 V ile çalışır mı?', 'a' => 'Hayır. Sumak SDT 50/3 C ve SDT 75/3 C modelleri 380 V (trifaze) çalışır.'],
                    ['q' => 'Karıştırıcı sürekli çalışır mı?', 'a' => 'Karıştırıcı pompa çalıştığı sürece döner. Bu yapı motor yükünü artırdığı için debi ve basma değerlerine uygun model seçilmelidir.'],
                    ['q' => 'Bakımı nasıl yapılır?', 'a' => 'Çark, karıştırıcı ve mekanik salmastra düzenli kontrol edilmelidir. İş bittiğinde pompayı kısa süre temiz suda çalıştırmak, içinde kalan çamurun kurumasını önler.'],
                ]],
            ],
        ];
    }
};
