<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $changed = 0;

        foreach ($this->content() as $id => $fields) {
            $current = DB::table('categories')->where('id', $id)->first(['description', 'buying_guide', 'faq']);
            if ($current === null) {
                $this->misses[$id][] = 'kategori yok';

                continue;
            }

            $data = [];

            foreach (['description', 'buying_guide'] as $column) {
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

    /** @return array<int, array{description: array{0: string, 1: string}, buying_guide: array{0: string, 1: string}, faq: array{0: string, 1: list<array{q: string, a: string}>}}> */
    private function content(): array
    {
        return [
            130 => [
                'description' => ['<h2>Dalgıç Pompa Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Dalgıç pompa</strong>, motoru ve pompa gövdesiyle birlikte suyun içine daldırılarak çalışan elektrikli pompadır. Suyu emerek çekmek yerine bulunduğu yerden yukarı bastığı için yüzey pompalarının 7–8 metrelik emiş sınırına takılmaz. Bu kategoride Pedrollo, Sumak, Winpo ve Kaysu markalarının kuyu, drenaj, kirli su ve foseptik dalgıç pompaları yer alır.</p>
<h3>Dalgıç Pompa Çeşitleri</h3>
<ul>
<li><strong>Derin kuyu dalgıç pompaları:</strong> 4 inç sondaj ve artezyen kuyuları için Pedrollo 4SR, Sumak 4SD ve 4SDM, Winpo WNP serileri ile ayrı satılan dalgıç motorlar. Ayrıntılar için <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a> kategorisine bakabilirsiniz.</li>
<li><strong>Temiz su dalgıç pompaları:</strong> Depo, sarnıç, keson kuyu ve gölette temiz su için Pedrollo TOP MULTI ve Kaysu QDX gibi modeller. Tüm modeller <a href="/kategoriler/su-pompalari/dalgic-pompalar/temiz-su-dalgic-pompasi">temiz su dalgıç pompası</a> kategorisindedir.</li>
<li><strong>Drenaj dalgıç pompaları:</strong> Bodrum, otopark ve yağmur suyu tahliyesi için flatörlü Pedrollo TOP, Winpo QDP ve WNP, Kaysu SP modelleri. Bkz. <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompa</a>.</li>
<li><strong>Paslanmaz drenaj pompaları:</strong> Pedrollo RX ve RXm ile Winpo WNP QCK gibi paslanmaz çelik gövdeli modeller; korozyon riski olan ortamlar için <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj dalgıç pompa</a> kategorisinde yer alır.</li>
<li><strong>Kirli su dalgıç pompaları:</strong> Kum, tortu ve askıda madde içeren sular için Sumak SDF, SDT ve SDTV ile Kaysu HWD. Bkz. <a href="/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa">kirli su dalgıç pompa</a>.</li>
<li><strong>Foseptik ve bıçaklı pompalar:</strong> Tuvalet atığı ve lifli atık su için vortex çarklı <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a> ile atığı parçalayarak basan <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompalar</a>.</li>
</ul>
<p>Tekneler için <a href="/kategoriler/su-pompalari/dalgic-pompalar/sintine-pompasi">sintine pompası</a>, dipte çöken yoğun çamur için <a href="/kategoriler/su-pompalari/dalgic-pompalar/karistiricili-camur-pompasi">karıştırıcılı çamur pompası</a> kategorileri de bulunur.</p>
<h3>Dalgıç Pompa Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı belirleyen başlıca etkenler pompa tipi, motor gücü, basma yüksekliği ve debi, gövde malzemesi (plastik, döküm veya paslanmaz) ile monofaze ya da trifaze besleme tipidir. Plastik gövdeli küçük bir drenaj pompası ile yüksek basmalı bir derin kuyu pompası arasında bu yüzden büyük fark bulunur. Maliyet kalemlerini <a href="/blog/dalgic-pompa-fiyatlari-2026-rehberi">dalgıç pompa maliyetini belirleyen etkenler</a> yazımızda anlattık.</p>
HTML],
                'buying_guide' => ['<h3>Dalgıç Pompa Seçim Rehberi</h3>', <<<'HTML'
<h3>Dalgıç Pompa Nasıl Seçilir?</h3>
<p>Doğru dalgıç pompa için önce suyun türü ve içindeki katı madde belirlenir, ardından gereken debi ve basma yüksekliği hesaplanır.</p>
<ul>
<li><strong>Suyun türü:</strong> Temiz su, az kirli yağmur suyu, kum ve tortulu su ya da foseptik atığı farklı pompa tipleri gerektirir. Ürün sayfalarındaki maksimum katı madde (partikül) geçişi değerini kontrol edin.</li>
<li><strong>Kuyu veya çukur ölçüsü:</strong> Derin kuyu pompaları 4 inç gövdelidir ve kuyu iç çapına rahatça sığmalıdır. Drenaj çukurunda flatörün serbest hareket edeceği alan bırakılmalıdır.</li>
<li><strong>Debi ve basma yüksekliği:</strong> Basma yüksekliğine suyun çıkacağı noktaya kadar olan kot farkı ile boru ve dirsek kayıpları da eklenir.</li>
<li><strong>Besleme:</strong> Evlerde genellikle 220 V monofaze, büyük güçlerde 380 V trifaze modeller kullanılır. Farkları <a href="/blog/monofaze-trifaze-dalgic-pompa-farki">monofaze ve trifaze dalgıç pompa farkı</a> yazımızda anlattık.</li>
<li><strong>Otomatik çalışma:</strong> Flatörlü modeller su seviyesine göre kendiliğinden çalışır ve durur. Flatörsüz modellerde seviye şamandırası veya kumanda panosu kullanılmalıdır.</li>
</ul>
<p>Tüm tiplerin karşılaştırması için <a href="/blog/dalgic-pompa-turleri-secim-rehberi">dalgıç pompa türleri seçim rehberini</a> okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Dalgıç pompa ile normal pompa arasındaki fark nedir?', [
                    ['q' => 'Dalgıç pompa ile yüzey pompası arasındaki fark nedir?', 'a' => 'Yüzey pompaları zemine kurulur ve suyu emerek çeker; emiş derinliği pratikte 7–8 metreyle sınırlıdır. Dalgıç pompa ise suyun içinde çalışır ve suyu bulunduğu yerden yukarı basar. Bu sayede derin kuyulardan su alabilir, emiş hattı ve hava yapma sorunu yaşamaz.'],
                    ['q' => 'Hangi iş için hangi dalgıç pompa kullanılır?', 'a' => 'Sondaj kuyusu için derin kuyu, depo ve sarnıç için temiz su, bodrum ve yağmur suyu için drenaj, kum ve tortulu su için kirli su, tuvalet atığı için foseptik veya bıçaklı dalgıç pompa kullanılır. Deniz kenarı gibi korozyon riski olan yerlerde paslanmaz gövdeli modeller tercih edilir.'],
                    ['q' => 'Flatörlü dalgıç pompa ne demek?', 'a' => 'Flatör (şamandıra), su seviyesi yükselince pompayı çalıştıran, seviye düşünce durduran anahtardır. Flatörlü pompalar bodrum ve çukurlarda otomatik tahliye sağlar ve susuz çalışma riskini azaltır. Dar çukurlar için flatörü gövdeye entegre gizli flatörlü modeller de vardır. Ayrıntılar için <a href="/blog/flatorlu-dalgic-pompa-nasil-calisir">flatörlü dalgıç pompa nasıl çalışır</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Dalgıç pompa susuz çalışırsa ne olur?', 'a' => 'Dalgıç pompanın motoru çevresindeki suyla soğur. Susuz çalıştığında motor ısınır ve mekanik salmastra zarar görebilir. Bu yüzden flatör, seviye elektrotu veya kuru çalışma korumalı pano kullanılmalıdır.'],
                    ['q' => 'Dalgıç pompa bakımı nasıl yapılır?', 'a' => 'Kablo ve bağlantılar düzenli kontrol edilmeli, emiş ızgarası ve çark çevresindeki tortu temizlenmelidir. Kirli su ve foseptik pompalarını kullanım sonrasında temiz suda kısa süre çalıştırmak tıkanmayı azaltır. Uzun süre kullanılmayacak pompalar donmayan, kuru bir yerde saklanmalıdır. Ayrıntılar: <a href="/blog/dalgic-pompa-bakimi">dalgıç pompa bakımı</a>.'],
                ]],
            ],
            138 => [
                'description' => ['<h2>Derin Kuyu Dalgıç Pompa Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Derin Kuyu Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Derin kuyu dalgıç pompası</strong>, sondaj (artezyen) kuyularında suyun içine indirilerek çalışan, uzun ve dar gövdeli çok kademeli pompadır. Su seviyesi 7–8 metrenin altında kalan kuyularda yüzey pompaları suyu emerek çekemez; derin kuyu pompası ise suyu kuyunun içinden yukarı basar. Konut, bahçe, tarımsal sulama ve sanayi suyu için kullanılır.</p>
<p>Bu kategorideki modellerin tamamı <strong>4 inç (95–100 mm)</strong> gövdelidir. 0.75–10 HP motor gücü, 2–19 m³/h debi ve modele göre 400 metreyi aşan basma yüksekliği seçenekleri bulunur. 3 HP'ye kadar 220 V monofaze, daha büyük güçlerde 380 V trifaze modeller vardır.</p>
<h3>Markalar ve Seriler</h3>
<ul>
<li><strong>Pedrollo 4SR:</strong> Motorlu satılan 4 inç pompalar; 3.6, 6 ve 9 m³/h debi gruplarında 405 metreye kadar basma. Pedrollo 4PD (trifaze) ve 4PDm (monofaze) dalgıç motorlar ayrıca satılır.</li>
<li><strong>Sumak 4SD ve 4SDM:</strong> 4SD serisi 12 ton ve 18 ton gruplarında (10–12 ve 16–19 m³/h) 2" çıkışlıdır. 4SDM ve 4SDMT serileri 3 ton ve 6 ton gruplarında yüksek basma içindir. Sumak 4SM ve 4SMT dalgıç motorlar da bulunur.</li>
<li><strong>Winpo WNP:</strong> 3, 6, 8.4 ve 12 m³/h debi gruplarında monofaze ve trifaze modeller. Winpo 4SKM keson kuyu dalgıç pompaları da bu kategoridedir.</li>
</ul>
<h3>Derin Kuyu Pompası Fiyatları</h3>
<p>Fiyatı en çok motor gücü, kademe sayısı (basma yüksekliği), debi grubu ve monofaze ya da trifaze seçimi belirler. Kategoride komple motorlu pompaların yanında yalnızca dalgıç motorlar da bulunduğu için ürün adındaki “Motorlu” ve “Motoru” ifadelerine dikkat edin. Ayrıntılar için <a href="/blog/derin-kuyu-pompa-fiyatlari-2026-rehberi">derin kuyu pompa maliyetini belirleyen etkenler</a> yazımıza bakabilirsiniz.</p>
<p>Markaları karşılaştırmak için <a href="/marka/pedrollo">Pedrollo</a>, <a href="/marka/sumak">Sumak</a> ve <a href="/marka/winpo">Winpo</a> marka sayfalarına, diğer tipler için <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompalar</a> kategorisine göz atabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Derin Kuyu Pompası Seçim Rehberi</h3>', <<<'HTML'
<h3>Derin Kuyu Pompası Nasıl Seçilir?</h3>
<p>Seçim için kuyu raporundaki dört değer gerekir: kuyu iç çapı, statik su seviyesi, pompa çalışırken düşen dinamik su seviyesi ve kuyunun verebildiği debi.</p>
<ul>
<li><strong>Kuyu çapı:</strong> 4 inç pompalar için kuyu iç çapının genellikle en az 110 mm olması önerilir. Pompa ile kuyu cidarı arasında motoru soğutacak su akışı için boşluk kalmalıdır.</li>
<li><strong>Debi:</strong> Pompa debisi kuyunun verebildiği sudan fazla olmamalıdır. Aksi halde su seviyesi pompaya kadar düşer ve pompa susuz kalır.</li>
<li><strong>Basma yüksekliği:</strong> Dinamik su seviyesi, kuyu başından kullanım noktasına kot farkı, boru kayıpları ve hidrofor varsa istenen basınç toplanır. Örneğin 60 m dinamik seviye ve 3 bar (yaklaşık 30 m) kullanım basıncı için 90 metrenin üzerinde basma gerekir.</li>
<li><strong>Besleme:</strong> Trifaze elektrik yoksa 3 HP'ye kadar monofaze modeller arasından seçim yapılır.</li>
</ul>
<h3>Koruma ve Montaj</h3>
<p>Pompa kuyu dibine değmeyecek şekilde asılmalı ve dinamik su seviyesinin altında kalmalıdır. Susuz çalışmaya karşı seviye elektrotu veya kuru çalışma korumalı pano, pompa çıkışına da çekvalf kullanılmalıdır. Ayrıntılar için <a href="/blog/kuyu-dalgic-pompa-secimi-derinlik-rehberi">derinlik ve debiye göre kuyu pompası seçimi</a> ile <a href="/blog/derin-kuyu-kuru-calisma-korumasi">derin kuyu kuru çalışma koruması</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Derin kuyu pompası için kuyu sondaj çapı ne kadar olmalı?', [
                    ['q' => 'Derin kuyu pompası ile kuyu pompası aynı şey mi?', 'a' => 'Günlük dilde “kuyu pompası” hem sondaj kuyusundaki derin kuyu dalgıç pompasını hem de geniş çaplı keson kuyulardaki pompaları anlatır. Dar çaplı sondaj ve artezyen kuyuları için 4 inç derin kuyu dalgıç pompası, beton halkalı geniş kuyular için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/keson-kuyu-pompa">keson kuyu pompası</a> kullanılır.'],
                    ['q' => 'Derin kuyu pompası için kuyu çapı ne kadar olmalı?', 'a' => 'Bu kategorideki pompaların dış çapı 95–100 mm\'dir. Kuyu iç çapının genellikle en az 110 mm olması önerilir; daha dar kuyularda pompa sıkışabilir ve motor yeterince soğuyamaz. Kuyu iç çapını sondaj raporundan veya kuyuyu açan firmadan öğrenebilirsiniz.'],
                    ['q' => 'Kaç HP derin kuyu pompası gerekir?', 'a' => 'Gerekli güç, istenen debi ve toplam basma yüksekliğine göre belirlenir. Örneğin Pedrollo 4SR 4 serisinde 6 m³/h debide 2 HP model 120 metreye, 3 HP model 170 metreye kadar basar. Ürün adlarındaki HP, mss ve m³/h değerleri karşılaştırma için iyi bir başlangıçtır.'],
                    ['q' => 'Derin kuyu pompası monofaze mi trifaze mi olmalı?', 'a' => 'Yalnızca 220 V elektrik varsa monofaze model seçilir; bu kategoride monofaze seçenekler 3 HP\'ye kadar çıkar. 4 HP ve üzeri modeller 380 V trifazedir. Trifaze elektrik bulunan tarım ve sanayi kuyularında aynı güçte trifaze model de tercih edilebilir.'],
                    ['q' => 'Derin kuyu pompası neden az su veriyor?', 'a' => 'En sık nedenler kuyudaki su seviyesinin düşmesi, kum ve silt nedeniyle çark aşınması, borularda kaçak veya çekvalf arızasıdır. Önce kuyunun statik ve dinamik seviyesi ölçülmelidir. Ayrıntılı teşhis için <a href="/blog/derin-kuyu-debi-dususu-teshis">derin kuyu debi düşüşü</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
            145 => [
                'description' => ['<p>Drenaj dalgıç pompa; bodrum katları', <<<'HTML'
<h2>Drenaj Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Drenaj dalgıç pompası</strong>; bodrum, otopark, asansör kuyusu, inşaat çukuru ve bahçe drenaj hatlarında biriken yağmur suyunu ve az kirli suyu tahliye eden pompadır. Suyun içinde çalıştığı için emiş hattı gerekmez. Çoğu model flatörlüdür; su yükselince kendiliğinden çalışır, çukur boşalınca durur.</p>
<h3>Drenaj Pompası Çeşitleri</h3>
<ul>
<li><strong>Plastik gövdeli modeller:</strong> Teknopolimer gövdeli Pedrollo TOP serisi ve Kaysu SP modelleri; ev, bodrum ve bahçe kullanımı için hafif seçeneklerdir.</li>
<li><strong>Winpo QDP ve WNP A/AW:</strong> Flatörlü ve gizli flatörlü (GF) ev tipi drenaj pompaları. AW kodlu modellerde 35 mm'ye kadar katı madde geçişi vardır.</li>
<li><strong>Paslanmaz gövdeli modeller:</strong> Pedrollo RX, RXm ve DM N ile Winpo WNP QCK; deniz kenarı ve korozyon riski olan yerlerde uzun ömür sağlar. Paslanmaz modellerin hepsi için <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj dalgıç pompa</a> kategorisine bakabilirsiniz.</li>
<li><strong>Yüksek basmalı modeller:</strong> Winpo WNP PF serisi ile WNP 6-28/2 ve 6-39/3 modelleri 40 mSS'ye kadar basma sunar; suyun uzağa veya yükseğe atılması gereken yerlerde kullanılır.</li>
<li><strong>Sıfırdan emişli ve yağmur suyu modelleri:</strong> Pedrollo TOP2 FLOOR zemindeki suyu çok düşük seviyeye kadar çeker. Winpo WNP V serisi yağmur suyu tahliyesi içindir.</li>
</ul>
<p>Bu kategoride Pedrollo, Winpo ve Kaysu modelleri yer alır. Kum, çamur ve tortulu sular için <a href="/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa">kirli su dalgıç pompası</a>, tuvalet atığı için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompa</a> kullanılmalıdır.</p>
HTML],
                'buying_guide' => ['<h3>Drenaj Dalgıç Pompa Seçim Rehberi</h3>', <<<'HTML'
<h3>Drenaj Pompası Nasıl Seçilir?</h3>
<p>Drenaj pompası seçerken suyun ne kadar kirli olduğu, saatte ne kadar su toplandığı ve suyun ne kadar yükseğe atılacağı birlikte düşünülür.</p>
<ul>
<li><strong>Katı madde geçişi:</strong> Temiz yağmur suyu için küçük geçişli modeller yeterlidir. Yaprak, kum ve tortu varsa 30–35 mm geçişli modeller seçilmelidir. Bu değer ürün sayfalarında “maksimum partikül çapı” olarak yazılır.</li>
<li><strong>Debi:</strong> Ev bodrumu ve küçük çukurlar için genellikle 6–12 m³/h, otopark ve geniş alanlar için 15 m³/h ve üzeri modeller değerlendirilir.</li>
<li><strong>Basma yüksekliği:</strong> Çukur derinliği, çıkış noktasına kadar kot farkı ve hortum ya da boru uzunluğu hesaba katılır. Pompa, maksimum basma değerine yaklaştıkça daha az su verir.</li>
<li><strong>Çukur ölçüsü:</strong> Dar çukurlarda dış flatör duvara takılabilir; bu durumda gizli flatörlü (Pedrollo GM, Winpo GF) modeller tercih edilir.</li>
</ul>
<p>Çukur boyutu ve çalışma aralığı için <a href="/blog/drenaj-cukuru-hacmi-hesabi-tasma-payi">drenaj çukuru hacmi hesabı</a>, genel seçim için <a href="/blog/drenaj-dalgic-pompa-rehberi-bodrum-yagmur-suyu">drenaj dalgıç pompa rehberi</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Drenaj pompası ile foseptik pompası arasındaki fark nedir?', [
                    ['q' => 'Drenaj pompası ile foseptik pompası arasındaki fark nedir?', 'a' => 'Drenaj pompası yağmur suyu ve az kirli su içindir; katı madde geçişi sınırlıdır. Foseptik pompası tuvalet atığı ve lifli atık su için geniş geçişli vortex çark veya parçalayıcı bıçakla çalışır. Drenaj pompasını foseptik çukurunda kullanmak tıkanmaya ve arızaya yol açar.'],
                    ['q' => 'Bodrum için hangi drenaj pompası seçilmeli?', 'a' => 'Ev bodrumlarında genellikle 220 V, flatörlü ve 6–12 m³/h debili bir drenaj pompası yeterlidir. Su girişi fazlaysa veya otopark gibi geniş bir alan söz konusuysa daha yüksek debili ya da iki pompalı bir sistem düşünülmelidir. Elektrik kesintisi riskine karşı yüksek seviye alarmı eklenebilir.'],
                    ['q' => 'Gizli flatörlü drenaj pompası nedir?', 'a' => 'Gizli flatörlü pompalarda şamandıra gövdenin içinde veya gövdeye bitişiktir. Dışarıda serbest hareket eden şamandıra olmadığı için dar çukurlarda takılma riski azdır. Pedrollo\'nun GM ve Winpo\'nun GF kodlu modelleri bu tiptedir.'],
                    ['q' => 'Drenaj pompası sürekli çalışmalı mı?', 'a' => 'Hayır. Flatörlü pompa su belirli seviyeye yükselince çalışır, çukur boşalınca durur. Pompa çok sık açılıp kapanıyorsa çukur küçük, flatör aralığı dar veya çekvalf arızalı olabilir. Hiç durmuyorsa su girişi pompa kapasitesini aşıyor ya da flatör takılı kalmış olabilir. Ayrıntılar için <a href="/blog/drenaj-pompa-flator-cevrim-start-stop">flatörlü drenaj pompa çevrimi</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Paslanmaz drenaj pompası ne zaman gerekir?', 'a' => 'Deniz kenarı, tuzlu veya hafif kimyasal içerikli su ve sürekli nemli ortamlarda paslanmaz çelik gövdeli modeller daha uzun ömürlüdür. Pedrollo RX, RXm ve Winpo WNP QCK serileri <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj dalgıç pompa</a> kategorisinde yer alır.'],
                ]],
            ],
            148 => [
                'description' => ['<h2>Kirli Su Dalgıç Pompa Modelleri ve Fiyatları</h2>', <<<'HTML'
<h2>Kirli Su Dalgıç Pompa Modelleri ve Fiyatları</h2>
<p><strong>Kirli su dalgıç pompası</strong>; kum, ince çamur, tortu ve askıda madde içeren suları tahliye etmek için kullanılan dalgıç pompadır. Şantiye ve kazı çukurları, su basan bodrumlar, asansör kuyuları, tarım kanalları ve sanayi atık su çukurları başlıca kullanım alanlarıdır. Drenaj pompalarına göre daha geniş katı madde geçişi ve daha dayanıklı gövde yapısı sunar.</p>
<h3>Kirli Su Pompası Modelleri</h3>
<ul>
<li><strong>Sumak SDF ve SDT:</strong> SDF monofaze (220 V), SDT trifaze (380 V) kirli su pompalarıdır. Paslanmaz gövdeli ve komple paslanmaz (İNOX) versiyonları bulunur; 1.5–4 HP güç ve 50 m³/h'e kadar debi seçenekleri vardır.</li>
<li><strong>Sumak SDTV:</strong> Gömlek soğutmalı trifaze modeller; 75 m³/h'e kadar debiyle yoğun tahliye işleri içindir.</li>
<li><strong>Sumak SDTY:</strong> Az kirli su için 51–75 mSS basma sınıfında yüksek basmalı trifaze modeller.</li>
<li><strong>Kaysu HWD-1100S:</strong> Paslanmaz gövdeli, 40 mm'ye kadar katı madde geçişli, 2" çıkışlı monofaze model.</li>
<li><strong>Winpo:</strong> Az kirli su için flatörlü WNP AW (35 mm katı geçişi), QDP, WNP PF ve paslanmaz WNP QCK modelleri.</li>
</ul>
<p>Satın almadan önce ürün sayfasındaki maksimum katı madde geçişini kontrol edin. Tuvalet atığı ve lifli atık için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik</a> veya <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompa</a>, dipte çöken yoğun çamur için <a href="/kategoriler/su-pompalari/dalgic-pompalar/karistiricili-camur-pompasi">karıştırıcılı çamur pompası</a> daha uygundur. Temiz yağmur suyu için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompaları</a> yeterlidir.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Kirli Su Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Katı madde boyutu:</strong> Suyun içindeki en iri parça, pompanın katı madde geçişinden küçük olmalıdır. Taş ve çakıl varsa pompa ızgaralı bir sepet içine yerleştirilebilir.</li>
<li><strong>Debi ve basma:</strong> Şantiye ve sel tahliyesinde yüksek debi, suyun uzağa veya yükseğe atılması gereken yerlerde yüksek basma öne çıkar. Aynı pompada ikisi birden en yüksek değerde olmaz; ürün sayfalarındaki değerleri birlikte değerlendirin.</li>
<li><strong>Gövde malzemesi:</strong> Kumlu ve aşındırıcı sularda döküm veya paslanmaz gövdeli modeller plastik gövdeye göre daha dayanıklıdır.</li>
<li><strong>Besleme ve otomatik çalışma:</strong> Şantiyede genellikle 380 V trifaze, ev ve bodrumda 220 V monofaze kullanılır. Flatörsüz modellerde seviye şamandırası veya kumanda panosu eklenmelidir.</li>
</ul>
<p>Doğru modeli seçmek için <a href="/blog/kirli-su-dalgic-pompa-nedir-secim">kirli su dalgıç pompa seçimi</a> ve <a href="/blog/kirli-su-tahliye-pompasi-secimi">kirli su tahliye pompası seçimi</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Kirli su pompası ile drenaj pompası arasındaki fark nedir?', [
                    ['q' => 'Kirli su pompası ile drenaj pompası arasındaki fark nedir?', 'a' => 'Drenaj pompası yağmur suyu ve az kirli su içindir. Kirli su pompası kum, çamur ve tortulu suları daha geniş katı madde geçişi ve daha dayanıklı gövdeyle basar. Katı madde miktarı arttıkça kirli su pompası seçmek hem tıkanmayı hem aşınmayı azaltır.'],
                    ['q' => 'Kirli su pompası foseptik için kullanılır mı?', 'a' => 'Tuvalet atığı, bez ve lifli malzeme içeren sular için önerilmez; bu malzemeler çarka sarılarak pompayı tıkayabilir. Foseptik çukurları için vortex çarklı foseptik pompalar veya atığı parçalayan bıçaklı dalgıç pompalar kullanılmalıdır.'],
                    ['q' => 'Şantiyede kirli su pompası kullanırken nelere dikkat edilmeli?', 'a' => 'Pompa kablosundan değil, sapından veya askı halatından taşınmalı ve kaldırılmalıdır. Elektrik hattında kaçak akım rölesi bulunmalı, kablo ek yerleri suya girmemelidir. Pompanın çamura gömülmemesi için düz bir zemin veya taş üzerine yerleştirilmesi önerilir.'],
                    ['q' => 'Kum ve tortu pompayı neden yıpratır?', 'a' => 'Kum ve ince taş, çark ile salmastra çevresinde aşınmaya yol açar ve zamanla debiyi düşürür. Aşınmayı azaltmak için pompayı çukurun dibindeki çökeltiye oturtmamak, kullanım sonrasında temiz suda kısa süre çalıştırmak ve katı madde değerine uygun model seçmek gerekir.'],
                    ['q' => 'Kirli su pompası monofaze mi trifaze mi olmalı?', 'a' => 'Ev ve bodrum kullanımında Sumak SDF veya Kaysu HWD-1100S gibi 220 V monofaze modeller yeterlidir. Şantiye ve sanayi gibi trifaze elektrik bulunan yerlerde Sumak SDT, SDTV ve SDTY gibi 380 V trifaze modeller daha yüksek debi ve basma sunar.'],
                ]],
            ],
        ];
    }
};
