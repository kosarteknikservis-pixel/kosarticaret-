<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SP = '/kategoriler/su-pompalari';

    private const SO = '/kategoriler/su-pompalari/ozel-amacli-pompalar';

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
        $sp = self::SP;
        $so = self::SO;
        $sd = self::SD;

        return [
            136 => [
                'meta_description' => ['Yangın söndürme, havuz, jakuzi, foseptik tahliye', 'Yangın pompaları, havuz ve jakuzi pompaları, keson kuyu pompaları, foseptik tahliye cihazları, dizel motopomplar: Sumak, Pedrollo ve Winpo modelleri.'],
                'description' => ['<h2>Özel Amaçlı Pompa Çeşitleri ve Fiyatları</h2>', <<<HTML
<h2>Özel Amaçlı Pompa Çeşitleri ve Fiyatları</h2>
<p><strong>Özel amaçlı pompalar</strong>, belirli bir iş için tasarlanmış pompa gruplarıdır: yangın söndürme tesisatını beslemek, havuz suyunu filtreden geçirmek, keson kuyudan su çekmek, bodrumdaki atık suyu kanalizasyona basmak veya elektriğin olmadığı arazide dizel motorla su basmak gibi.</p>
<p>Bu kategoride Sumak, Pedrollo ve Winpo markalarının özel amaçlı pompaları yer alır.</p>
<h3>Özel Amaçlı Pompa Türleri</h3>
<ul>
<li><strong>Yangın pompaları:</strong> Sumak SHT elektrikli yangın hidroforları ile SMKT 750 dizel ve joker pompalı yangın grupları. Bkz. <a href="{$so}/yangin-pompalari">yangın pompaları</a>.</li>
<li><strong>Keson kuyu pompaları:</strong> Geniş çaplı kuyulara indirilen Pedrollo UP ve TOP MULTI, Sumak 5SD ve Winpo 4SKM dalgıç pompaları. Bkz. <a href="{$so}/keson-kuyu-pompa">keson kuyu pompaları</a>.</li>
<li><strong>Ön filtreli havuz pompaları:</strong> Girişinde sepet filtre bulunan Sumak SMH ve Winpo Pool modelleri. Bkz. <a href="{$so}/on-filtreli-havuz-pompasi">havuz pompaları</a>.</li>
<li><strong>Jakuzi pompaları:</strong> Spa ve hidromasaj sistemleri için Sumak SMJB-K modelleri. Bkz. <a href="{$so}/jakuzi-pompasi">jakuzi pompaları</a>.</li>
<li><strong>Foseptik tahliye cihazları:</strong> Kanalizasyon kotunun altındaki WC, duş ve lavabo atık suyunu yukarı basan Sumak SMAC modelleri. Bkz. <a href="{$so}/foseptik-tahliye-cihazi">foseptik tahliye cihazları</a>.</li>
<li><strong>Dizel su motorları:</strong> Dizel motorla çalışan Sumak motopomplar; elektriğin olmadığı yerler içindir. Bkz. <a href="{$so}/dizel-su-motorlari">dizel su motorları</a>.</li>
<li><strong>Klapeli pompalar:</strong> Gövdesinde klape bulunan 2 inç ağızlı Sumak DSM ve DSMT santrifüj pompalar. Bkz. <a href="{$so}/klapeli-pompalar">klapeli pompalar</a>.</li>
<li><strong>Yağmur suyu tahliye pompaları:</strong> Bodrum, garaj ve bahçe çukurlarında biriken suyu boşaltan Winpo WNP V modelleri. Bkz. <a href="{$so}/yagmur-suyu-tahliye-pompasi">yağmur suyu tahliye pompaları</a>.</li>
</ul>
<h3>Özel Amaçlı Pompa Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca pompa tipi, motor gücü, pompa sayısı (tek, çift veya üç pompalı gruplar), kontrol panosu, gövde malzemesi ve elektrik beslemesi belirler. Dizel modellerde motor da fiyatın önemli bir kısmını oluşturur.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Hangi İş İçin Hangi Pompa?</h3>
<ul>
<li>Sprinkler, hidrant ve yangın dolabı hattını beslemek için <a href="{$so}/yangin-pompalari">yangın pompası</a>. Seçim yangın projesindeki debi ve basınca göre yapılır.</li>
<li>Beton halka keson kuyudan ev veya bahçe için su çekmek için <a href="{$so}/keson-kuyu-pompa">keson kuyu pompası</a>. Dar sondaj kuyuları için <a href="{$sd}/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a> gerekir.</li>
<li>Yüzme havuzu suyunu filtreden geçirmek için <a href="{$so}/on-filtreli-havuz-pompasi">ön filtreli havuz pompası</a>.</li>
<li>Bodrum katındaki WC ve duşun atık suyunu kanalizasyona basmak için <a href="{$so}/foseptik-tahliye-cihazi">foseptik tahliye cihazı</a>. Büyük foseptik çukurları için <a href="{$sd}/foseptik-dalgic-pompa">foseptik dalgıç pompa</a> uygundur.</li>
<li>Elektrik olmayan tarla veya şantiyede su basmak için <a href="{$so}/dizel-su-motorlari">dizel su motoru</a>.</li>
<li>Yağmur suyu biriken çukur ve bodrumları boşaltmak için <a href="{$so}/yagmur-suyu-tahliye-pompasi">yağmur suyu tahliye pompası</a> veya <a href="{$sd}/drenaj-dalgic-pompa">drenaj dalgıç pompa</a>.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/ozel-amacli-pompalar-nedir-nasil-secilir">özel amaçlı pompalar nedir, nasıl seçilir</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Yangın pompası hangi standartlara uygun olmalı?', [
                    ['q' => 'Özel amaçlı pompa ne demek?', 'a' => 'Yangın söndürme, havuz filtrasyonu, keson kuyu, bodrum atık suyu tahliyesi veya elektriksiz arazide su basma gibi belirli bir iş için tasarlanmış pompalardır. Her grup kendi alt kategorisinde listelenir.'],
                    ['q' => 'Yangın pompası nasıl seçilir?', 'a' => 'Yangın pompası, yangın projesinde hesaplanan debi ve basınca göre seçilir. Pompa sayısı, dizel yedek ve joker pompa ihtiyacı da projeye göre belirlenir. Ayrıntılar için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari">yangın pompaları</a> sayfasına bakabilirsiniz.'],
                    ['q' => 'Havuz pompası seçiminde nelere bakılır?', 'a' => 'Havuz hacmi, suyun günde kaç kez filtreden geçirileceği, filtre tipi ve boru çapı belirleyicidir. Örneğin 40 m³ havuz günde 3 kez ve 10 saatte filtre edilecekse yaklaşık 12 m³/h debi gerekir.'],
                    ['q' => 'Dizel su motoru ne zaman tercih edilmeli?', 'a' => 'Elektrik bağlantısı olmayan tarla, bağ ve şantiyelerde ya da geçici su basma işlerinde tercih edilir. Elektrik bulunan ve sürekli çalışacak sistemlerde elektrikli pompa genellikle daha pratiktir.'],
                    ['q' => 'Foseptik tahliye cihazı ne işe yarar?', 'a' => 'Kanalizasyon hattının altında kalan WC, duş ve lavabonun atık suyunu haznesinde toplar ve yukarıdaki ana hatta basar. Bodrum katına banyo veya tuvalet eklerken kullanılır.'],
                ]],
            ],
            137 => [
                'meta_description' => ['Beton halka ve geniş çaplı kuyular için keson', 'Keson kuyu dalgıç pompaları: Pedrollo UP ve TOP MULTI, Sumak 5SD ve 5SDF, Winpo 4SKM ve WNP MF. Şamandıralı ve paslanmaz seçenekler, 220 V ve 380 V.'],
                'description' => ['<h2>Keson Kuyu Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Keson Kuyu Pompası Modelleri ve Fiyatları</h2>
<p><strong>Keson kuyu pompası</strong>, beton halkalarla yapılmış geniş çaplı kuyulara indirilerek suyu yukarı basan dalgıç pompadır. Ev, bahçe ve küçük tarla sulamasında, kuyudan depoya veya hidrofora su aktarmada kullanılır. Pompa suyun içinde çalıştığı için yüzey pompalarındaki 6–7 metrelik emiş sınırı yoktur.</p>
<p>Bu kategoride Pedrollo, Sumak ve Winpo markalarının keson kuyu pompaları yer alır.</p>
<h3>Keson Kuyu Pompası Modelleri</h3>
<ul>
<li><strong>Pedrollo UP ve UPm (5 inç):</strong> Flatörlü keson kuyu pompaları; 1–2 HP ve 51–100 mss basma. UPm modelleri 220 V, UP TRF modelleri 380 V'tur.</li>
<li><strong>Pedrollo TOP MULTI:</strong> Plastik gövdeli, flatörlü çok kademeli dalgıç pompalar; 0.5–0.75 HP ve 42 mss'ye kadar basma. TECH versiyonlarında otomatik basınç kontrolü (hidrofor fonksiyonu) bulunur.</li>
<li><strong>Sumak 5SD, 5SDF ve 5SDT:</strong> 30 metre kablolu ve panolu keson kuyu dalgıç pompaları; 1–1.5 HP ve 72 m'ye kadar basma. 5SDF modelleri şamandıralı, 5SDT modelleri 380 V'tur; 9 serisi paslanmazdır.</li>
<li><strong>Winpo 4SKM:</strong> 4 inç keson kuyu dalgıç pompaları; 1–1.5 HP, 55–98 mss basma ve 2–4 m³/h debi.</li>
<li><strong>Winpo WNP MF (5 inç):</strong> 1.3–2 HP ve 100 mss'ye kadar basma veren keson kuyu dalgıç pompaları.</li>
<li><strong>Winpo WNP 6-28/2 ve 6-39/3:</strong> Flatörlü, 2 inç çıkışlı pompalar; 33–40 mss basma ve 15 m³/h debi.</li>
</ul>
<p>Dar sondaj kuyuları için <a href="{$sd}/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompaları</a>, depo ve sarnıçlar için <a href="{$sd}/temiz-su-dalgic-pompasi">temiz su dalgıç pompaları</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Keson Kuyu Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Kuyudaki su seviyesi ile en yüksek kullanım noktası arasındaki dikey mesafeye boru kayıpları ve kullanım noktasında istenen basınç eklenir. 1 bar yaklaşık 10 metre su sütunudur.</li>
<li><strong>Debi:</strong> Kuyunun verebileceği sudan fazla debi seçilmemelidir; aksi hâlde kuyu kısa sürede boşalır ve pompa susuz kalır.</li>
<li><strong>Kuru çalışma koruması:</strong> Su seviyesi düşebilen kuyularda şamandıralı (flatörlü) model veya ayrı seviye kontrolü kullanılmalıdır.</li>
<li><strong>Kablo boyu:</strong> Pompa ile pano arasındaki mesafe hesaplanmalıdır; Sumak 5SD modelleri 30 m, Winpo 4SKM modelleri 10 m kabloyla gelir; diğer modellerde kablo boyu ürün sayfasında belirtilir.</li>
<li><strong>Kumlu su:</strong> Kum çarkları aşındırır. Pompa kuyu dibine oturtulmamalı, dipten yukarıda asılmalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/keson-kuyu-pompa-rehberi">keson kuyu pompası rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Keson kuyu için dalgıç pompa mı yüzey pompası mı?', [
                    ['q' => 'Keson kuyu için dalgıç pompa mı, yüzey pompası mı?', 'a' => 'Su seviyesi pompanın kurulacağı yerden 6–7 metreden daha aşağıdaysa yüzey pompası emiş yapamaz; dalgıç keson kuyu pompası gerekir. Su yüzeye yakınsa ve pompanın kuyu dışında kalması isteniyorsa <a href="/kategoriler/su-pompalari/jet-pompalar-derinden-emisli">jet pompa</a> kullanılabilir.'],
                    ['q' => 'Keson kuyu pompası kaç metreye su basar?', 'a' => 'Modele göre değişir. Bu kategorideki pompalar yaklaşık 30 ile 100 mss arasında basma sağlar; örneğin Sumak 5SD9 72 m, Winpo 4SKM 150 98 mss, Pedrollo UPm 2/6 100 mss\'ye kadar basar.'],
                    ['q' => 'Keson kuyuda şamandıra gerekli mi?', 'a' => 'Su seviyesi düşebilen kuyularda gereklidir. Pompa susuz kalırsa kısa sürede zarar görür. Şamandıralı model veya pano üzerinden seviye kontrolü kullanılmalıdır.'],
                    ['q' => 'Keson kuyu pompası ile derin kuyu pompası arasındaki fark nedir?', 'a' => 'Derin kuyu pompaları dar sondaj borularına girecek şekilde ince ve uzundur. Keson kuyu pompaları geniş çaplı kuyular içindir; genellikle daha kısa gövdelidir ve bazı modellerinde şamandıra veya pano birlikte gelir.'],
                    ['q' => 'Keson kuyu pompası hidrofor olarak kullanılabilir mi?', 'a' => 'Basınç tankı ve basınç şalteri veya elektronik kontrol ünitesiyle birlikte kurulduğunda evin su ihtiyacını basınçlı olarak karşılayabilir. Pedrollo TOP MULTI TECH modellerinde otomatik basınç kontrolü pompanın içindedir.'],
                ]],
            ],
            147 => [
                'meta_description' => ['Bodrum WC, duş ve lavabo atık suyu için', 'Bodrum WC, duş ve lavabo atık suyu için Sumak SMAC foseptik tahliye cihazları: termoplastik tanklı modeller ve klozet arkası öğütücülü pompa.'],
                'description' => ['<h2>Foseptik Tahliye Cihazı Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Foseptik Tahliye Cihazı Modelleri ve Fiyatları</h2>
<p><strong>Foseptik tahliye cihazı</strong> (atık su terfi ünitesi), kanalizasyon hattının altında kalan WC, duş, lavabo ve çamaşır makinesinin atık suyunu bir haznede toplayıp yukarıdaki ana hatta basan sistemdir. Bodrum katına banyo veya tuvalet eklerken ya da binanın kanalizasyon bağlantısı kullanım noktasından yüksekte kaldığında kullanılır.</p>
<p>Bu kategorideki modellerin tamamı Sumak markalıdır.</p>
<h3>Foseptik Tahliye Cihazı Modelleri</h3>
<ul>
<li><strong>Sumak SMAC 1800 A, 2200 A ve 2200 B:</strong> Termoplastik tanklı foseptik dalgıç pompa üniteleri. SMAC 1800 A 9 mss basma ve 9 m³/h, SMAC 2200 B 9 mss basma ve 18 m³/h debi verir.</li>
<li><strong>Sumak SMAC700 BR:</strong> Klozetin arkasına yerleştirilen, öğütücü bıçaklı kompakt WC pompası; 700 W.</li>
</ul>
<p>Büyük foseptik çukurları için <a href="{$sd}/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a>, katı atıkları parçalayarak basan sistemler için <a href="{$sd}/bicakli-dalgic-pompa">bıçaklı dalgıç pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Foseptik Tahliye Cihazı Nasıl Seçilir?</h3>
<ul>
<li><strong>Bağlanacak cihazlar:</strong> Yalnızca bir WC için klozet arkası öğütücülü model yeterli olabilir. Birden fazla WC, duş ve çamaşır makinesi bağlanacaksa tanklı model gerekir.</li>
<li><strong>Basma yüksekliği:</strong> Cihazdan kanalizasyon bağlantısına kadar olan dikey yükseklik ve boru kayıpları ürünün basma değerini aşmamalıdır.</li>
<li><strong>Geri akış:</strong> Basma hattına çekvalf takılmalı, hattın ana kanalizasyona bağlantısı geri tepmeyi önleyecek şekilde yapılmalıdır.</li>
<li><strong>Havalandırma:</strong> Tanklı modellerde hazne havalandırma hattı bina dışına verilmelidir.</li>
<li><strong>Kullanım:</strong> Islak mendil, bez, hijyenik ped ve yağ tıkanmaya yol açar; bu tür atıklar klozete atılmamalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/foseptik-tahliye-cihazi-rehberi">foseptik tahliye cihazı rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Foseptik tahliye cihazı ne işe yarar?', [
                    ['q' => 'Foseptik tahliye cihazı ne işe yarar?', 'a' => 'Kanalizasyon hattının altında kalan WC, duş veya lavabonun atık suyunu haznesinde toplar ve yukarıdaki ana hatta basar. Bodrum katına banyo veya tuvalet eklemek için kullanılır.'],
                    ['q' => 'Klozet arkası pompa ile tanklı cihaz arasındaki fark nedir?', 'a' => 'Klozet arkası pompa (Sumak SMAC700 BR) tek bir klozete bağlanan, öğütücü bıçaklı kompakt bir ünitedir. Tanklı modeller (SMAC 1800 ve 2200) haznesinde birden fazla cihazın atık suyunu toplar ve daha yüksek debiyle basar.'],
                    ['q' => 'Foseptik tahliye cihazı koku yapar mı?', 'a' => 'Hazne kapağı sızdırmaz, havalandırma hattı bina dışına verilmiş ve basma hattında çekvalf takılıysa koku yapmaz.'],
                    ['q' => 'Elektrik kesilince ne olur?', 'a' => 'Cihaz elektrikle çalışır; kesinti sırasında atık su basılmaz. Kesinti boyunca bağlı cihazların kullanılmaması gerekir. Kesintinin sık olduğu yerlerde jeneratör veya kesintisiz güç kaynağı düşünülebilir.'],
                    ['q' => 'Tuvalet kâğıdı ve ıslak mendil geçer mi?', 'a' => 'Tuvalet kâğıdı sorun yaratmaz. Islak mendil, bez ve hijyenik ped gibi lifli atıklar öğütücülü modellerde bile tıkanmaya yol açabilir; bu atıklar klozete atılmamalıdır.'],
                ]],
            ],
            151 => [
                'description' => ['<p>Ön filtreli havuz pompası, yüzme havuzlarında', <<<HTML
<h2>Ön Filtreli Havuz Pompası Modelleri ve Fiyatları</h2>
<p><strong>Ön filtreli havuz pompası</strong>, yüzme havuzu suyunu skimmer ve dip süzgeç hatlarından emip kum filtresi, varsa ısıtıcı ve geri dönüş hattı üzerinden havuza geri basan pompadır. Girişindeki sepet filtre yaprak, saç ve kaba parçaları tutarak çarkı ve ana filtreyi korur.</p>
<p>Bu kategoride Sumak ve Winpo markalarının havuz pompaları yer alır.</p>
<h3>Havuz Pompası Modelleri</h3>
<ul>
<li><strong>Sumak SMH ve SMHT:</strong> 0.85–3 HP, 2 inç giriş ve çıkış, 50 m³/h'e kadar debi ve 6 m emiş. SMH modelleri 220 V monofaze, SMHT ve SMH 85T modelleri 380 V trifazedir.</li>
<li><strong>Winpo Pool-1:</strong> 0.5–1.5 HP, 1½ inç giriş ve çıkış, 6 m emiş.</li>
<li><strong>Winpo Pool-2:</strong> 1.5–4 HP, 2 inç giriş ve çıkış, 50 m³/h'e kadar debi ve 6 m emiş.</li>
</ul>
<p>Winpo modellerinde adı M ile bitenler 220 V, T ile bitenler 380 V'tur.</p>
<p>Spa ve hidromasaj sistemleri için <a href="{$so}/jakuzi-pompasi">jakuzi pompaları</a>, klorlu veya tuzlu suyun transferi için <a href="{$sp}/santrifuj-pompalar/paslanmaz-pompalar-kimyasal">paslanmaz pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Havuz Pompası Seçim Rehberi</h3>', <<<HTML
<h3>Havuz Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Debi:</strong> Havuz suyunun günde 2–3 kez filtreden geçmesi hedeflenir. Hesap: havuz hacmi (m³) × günlük tur sayısı ÷ günlük çalışma saati. Örneğin 40 m³ havuz, günde 3 tur ve 10 saat çalışma için yaklaşık 12 m³/h gerekir.</li>
<li><strong>Filtre uyumu:</strong> Pompa debisi kum filtresinin önerilen debi aralığını aşmamalıdır; aşırı debi filtre verimini düşürür.</li>
<li><strong>Basma yüksekliği:</strong> Filtre, ısıtıcı, boru ve dirsek kayıpları toplanır. Pompa havuz su seviyesinin üstündeyse emiş mesafesi 6 m'yi aşmamalıdır.</li>
<li><strong>Bağlantı çapı:</strong> Pompa ağzı mevcut tesisatla uyumlu olmalıdır; Winpo Pool-1 1½ inç, Sumak SMH ve Winpo Pool-2 2 inçtir.</li>
<li><strong>Elektrik:</strong> Ev havuzlarında genellikle 220 V, büyük havuzlarda 380 V modeller kullanılır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/havuz-pompasi-secimi-rehberi">havuz pompası seçimi rehberi</a> ve <a href="/blog/havuz-on-filtre-sepet-tikanma-onleme">ön filtre sepeti tıkanması</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Havuz pompası günde kaç saat çalışmalı?', [
                    ['q' => 'Havuz pompası günde kaç saat çalışmalı?', 'a' => 'Havuz suyunun günde 2–3 kez filtreden geçmesini sağlayacak süre kadar çalışmalıdır. Yaz sezonunda bu genellikle günde 8–12 saattir; havuz hacmi, kullanım yoğunluğu ve sıcaklığa göre ayarlanır.'],
                    ['q' => 'Havuz pompası debisi nasıl hesaplanır?', 'a' => 'Havuz hacmi (m³) günlük tur sayısıyla çarpılır ve günlük çalışma saatine bölünür. Örneğin 40 m³ havuz, günde 3 tur ve 10 saat çalışma için 40 × 3 ÷ 10 = 12 m³/h debi gerekir.'],
                    ['q' => 'Ön filtre sepeti ne işe yarar?', 'a' => 'Pompa girişindeki sepet yaprak, saç ve kaba parçaları tutarak çarkın tıkanmasını ve zarar görmesini önler. Sepet düzenli olarak temizlenmelidir; dolu sepet debiyi düşürür ve pompanın sesli çalışmasına yol açar.'],
                    ['q' => 'Kışın havuz pompası ne yapılmalı?', 'a' => 'Donma riski olan bölgelerde pompa durdurulur, pompa gövdesi ve hatlardaki su boşaltılır. Pompa mümkünse kapalı ve kuru bir yerde saklanır. Ayrıntılar için <a href="/blog/havuz-pompasi-kislama-donma-korumasi">havuz pompası kışlama</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Havuz pompası sesli çalışıyorsa sebebi ne olabilir?', 'a' => 'Emme hattından hava girmesi, tıkalı ön filtre sepeti, düşük havuz su seviyesi veya aşınmış rulman ses yapabilir. Önce sepet ve emme hattı contaları kontrol edilmelidir.'],
                ]],
            ],
            155 => [
                'description' => ['<h2>Yangın Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Yangın Pompası Modelleri ve Fiyatları</h2>
<p><strong>Yangın pompası</strong>, sprinkler, yangın dolabı ve hidrant hatlarını yangın deposundan gereken debi ve basınçta besleyen pompa sistemidir. Türkiye'de sabit yangın söndürme sistemleri Binaların Yangından Korunması Hakkında Yönetmelik ve ilgili standartlar esas alınarak projelendirilir; pompanın debisi, basıncı ve pompa sayısı yangın projesinde belirlenir.</p>
<p>Bu kategorideki modellerin tamamı Sumak markalıdır.</p>
<h3>Yangın Pompası Grupları</h3>
<ul>
<li><strong>Sumak SHT elektrikli yangın hidroforları:</strong> Dikey kademeli SHT 12, 16, 24 ve 34 pompalarıyla kurulan gruplar; pompa başına 3–11 kW motor, 380 V. EY tek pompalı, EEY çift pompalı, EEEY üç pompalı gruptur.</li>
<li><strong>Sumak SMKT 750 dizel yangın grupları:</strong> DY dizel pompalı, DJY dizel ve joker pompalı, EDJY elektrikli, dizel ve joker pompalı gruptur. SMKT 750 ve SMKT 750/2 versiyonları vardır.</li>
</ul>
<p>Grup adlarındaki harfler pompaları gösterir: E elektrikli pompa, D dizel pompa, J joker (basınç takip) pompasıdır.</p>
<h3>Yangın Pompası Sisteminin Bileşenleri</h3>
<ul>
<li><strong>Elektrikli ana pompa:</strong> Hat basıncı düştüğünde otomatik devreye girer.</li>
<li><strong>Dizel yedek pompa:</strong> Elektrik kesildiğinde suyu basmaya devam eder; gerekip gerekmediği projeye göre belirlenir.</li>
<li><strong>Joker (jockey) pompa:</strong> Küçük kaçaklardan doğan basınç düşüşlerini karşılar, ana pompanın gereksiz yere çalışmasını önler.</li>
<li><strong>Kontrol panosu:</strong> Pompaları basınca göre otomatik çalıştırır ve arıza durumunu bildirir.</li>
</ul>
<p>SHT pompalarının teknik özellikleri için <a href="{$sp}/kademeli-pompalar/dikey-kademeli-pompalar">dikey kademeli pompalar</a> sayfasına bakabilirsiniz. Fiyatı belirleyen etkenler için <a href="/blog/yangin-pompa-fiyatlari-2026-rehberi">yangın pompası maliyeti rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Yangın Pompası Seçim Kriterleri</h3>', <<<HTML
<h3>Yangın Pompası Seçim Kriterleri</h3>
<ul>
<li><strong>Proje değerleri:</strong> Debi ve basınç, yangın projesindeki hidrolik hesaba göre belirlenir. Konut hidroforu gibi daire sayısına göre seçim yapılmaz.</li>
<li><strong>Pompa yapısı:</strong> Elektrikli, dizel ve joker pompa ihtiyacı yapının risk sınıfına ve projeye göre belirlenir.</li>
<li><strong>Standart ve onay:</strong> Seçilen grubun projede istenen standart ve onay şartlarını karşıladığı proje müellifi ve yetkili yangın firmasıyla doğrulanmalıdır.</li>
<li><strong>Kurulum:</strong> Yangın deposu, emiş hattı, test hattı ve kontrol panosu yetkili yangın sistemleri firmasıyla planlanmalıdır.</li>
<li><strong>Bakım:</strong> Pompalar düzenli aralıklarla test çalıştırmasıyla denetlenmeli ve bakım kayıtları tutulmalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/yangin-pompasi-nedir-nasil-secilir">yangın pompası nedir, nasıl seçilir</a>, <a href="/blog/elektrikli-dizel-yangin-pompa-sistemi">elektrikli ve dizel yangın pompası sistemi</a> ve <a href="/blog/yangin-hidroforu-ile-konut-hidrofor-farki">yangın hidroforu ile konut hidroforu farkı</a> yazılarımızı okuyabilirsiniz.</p>
<p>Proje debi ve basınç değerlerinizle <a href="/iletisim">teknik teklif isteyebilirsiniz</a>.</p>
HTML],
                'faq' => ['Yangın pompası sistemi zorunlu mu? Hangi binalarda gerekli?', [
                    ['q' => 'Yangın pompası hangi binalarda gerekli?', 'a' => 'Binaların Yangından Korunması Hakkında Yönetmelik, yapının yüksekliğine, kullanım amacına ve alanına göre sprinkler veya yangın dolabı gibi sabit söndürme sistemleri ister; bu sistemler yangın pompasıyla beslenir. Binanızın kapsamda olup olmadığını proje müellifi veya yetkili yangın firması belirler. Ayrıntılar için <a href="/blog/yangin-pompasi-hangi-binalarda-zorunlu">yangın pompası hangi binalarda zorunlu</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Elektrikli ve dizel yangın pompası neden birlikte kullanılır?', 'a' => 'Yangın sırasında bina elektriği kesilebilir. Dizel pompa elektrikten bağımsız çalıştığı için bu durumda da suyu basmaya devam eder. Dizel yedek pompanın gerekip gerekmediği projeye göre belirlenir.'],
                    ['q' => 'Joker pompa ne işe yarar?', 'a' => 'Joker (jockey) pompa, tesisattaki küçük kaçaklardan doğan basınç düşüşlerini karşılayarak hattı sürekli basınçlı tutar. Böylece ana pompa yalnızca gerçek su çekişinde devreye girer. Ayrıntılar için <a href="/blog/jockey-pompa-yangin-sistemi-nedir">joker pompa nedir</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Grup adındaki EY, EEY ve EDJY ne anlama gelir?', 'a' => 'Harfler gruptaki pompaları gösterir: E elektrikli pompa, D dizel pompa, J joker pompadır. Örneğin EEY iki elektrikli pompalı, EDJY bir elektrikli, bir dizel ve bir joker pompalı gruptur.'],
                    ['q' => 'Yangın pompasını kim kurabilir?', 'a' => 'Yangın söndürme sistemlerinin kurulumu yetkili yangın sistemleri firmaları tarafından yapılmalıdır. Koşar Ticaret yangın pompası satışı yapar; pompa seçimi için proje değerlerinizle bize ulaşabilirsiniz.'],
                ]],
            ],
            160 => [
                'meta_description' => ['Spa ve hidromasaj sistemleri için sessiz', 'Spa ve hidromasaj sistemleri için Sumak SMJB-K jakuzi pompaları: kapalı fanlı, 0.85–1.5 HP, 1½ inç bağlantı, 220 V ve 380 V modeller.'],
                'description' => ['<p>Jakuzi pompası, spa ve jakuzi kabinlerinde', <<<HTML
<h2>Jakuzi Pompası Modelleri ve Fiyatları</h2>
<p><strong>Jakuzi pompası</strong>, spa ve hidromasaj küvetlerinde suyu emip masaj jetlerine basan, filtre ve ısıtıcı hattında suyu dolaştıran pompadır. Seçim jet sayısına, bağlantı çapına ve mevcut sistemin istediği debiye göre yapılır.</p>
<p>Bu kategorideki modellerin tamamı Sumak markalıdır.</p>
<h3>Sumak SMJB-K Jakuzi Pompaları</h3>
<ul>
<li><strong>SMJB-K85 ve SMJB-K85T:</strong> 0.85 HP, 11–15 m³/h debi.</li>
<li><strong>SMJB-K100 ve SMJB-K100T:</strong> 1 HP, 16–25 m³/h debi.</li>
<li><strong>SMJB-K150 ve SMJB-K150T:</strong> 1.5 HP, 16–25 m³/h debi.</li>
</ul>
<p>Tüm modeller kapalı fanlıdır; giriş ve çıkış 1½ inç, emiş derinliği 6 m'dir. Adı T ile biten modeller 380 V trifaze, diğerleri 220 V monofazedir.</p>
<p>Yüzme havuzu filtrasyonu için <a href="{$so}/on-filtreli-havuz-pompasi">ön filtreli havuz pompaları</a>, tüm seçenekler için <a href="{$so}">özel amaçlı pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Jakuzi Pompası Nasıl Seçilir?</h3>', <<<HTML
<h3>Jakuzi Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Mevcut pompanın değerleri:</strong> Değiştirilecek pompanın etiketindeki güç, debi ve bağlantı çapı yeni pompa için en güvenilir başlangıç noktasıdır.</li>
<li><strong>Jet sayısı:</strong> Jet sayısı arttıkça gereken debi artar. Az jetli küçük küvetlerde 0.85 HP, çok jetli sistemlerde 1–1.5 HP modeller değerlendirilir.</li>
<li><strong>Bağlantı çapı:</strong> SMJB-K modellerinin giriş ve çıkışı 1½ inçtir; farklı çaplı tesisatta uygun redüksiyon kullanılmalıdır.</li>
<li><strong>Montaj:</strong> Pompa titreşimi gövdeye aktarmayacak şekilde sabitlenmeli, emme hattında hava kaçağı olmamalıdır.</li>
<li><strong>Elektrik:</strong> Ev tipi jakuzilerde genellikle 220 V modeller kullanılır; pompa hattı kaçak akım rölesiyle korunmalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/jakuzi-pompasi-secimi-rehberi">jakuzi pompası seçimi rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Jakuzi pompası ile havuz pompası aynı mı?', [
                    ['q' => 'Jakuzi pompası ile havuz pompası aynı mı?', 'a' => 'Hayır. Havuz pompasının girişinde sepet filtre bulunur ve havuz suyunu kum filtresinden geçirmek için kullanılır. Jakuzi pompası ise hidromasaj jetlerini besler ve küvet tesisatına bağlanır.'],
                    ['q' => 'Ürün adında "asit pompası" geçmesi ne anlama gelir?', 'a' => 'Sumak SMJB-K modelleri "jakuzi ve asit pompası" adıyla sunulur. Bu, pompanın her kimyasal sıvıya uygun olduğu anlamına gelmez. Kimyasal sıvılar için sıvının adı ve derişimiyle malzeme uyumu kontrol edilmeli, gerekirse <a href="/kategoriler/su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal">paslanmaz pompalar</a> değerlendirilmelidir.'],
                    ['q' => 'Jakuzi pompası kaç saat çalışmalı?', 'a' => 'Masaj pompası kullanım sırasında çalışır. Filtreleme ve devridaim süresi jakuzi üreticisinin önerisine göre programlanmalıdır.'],
                    ['q' => 'Jakuzi pompası gürültülü çalışıyorsa ne yapılmalı?', 'a' => 'Emme hattında hava kaçağı, tıkalı filtre, sabitlenmemiş pompa gövdesi veya aşınmış rulman ses yapabilir. Önce emme hattı bağlantıları ve pompanın montajı kontrol edilmelidir.'],
                    ['q' => 'Mevcut jakuzime uygun pompayı nasıl bulurum?', 'a' => 'Jakuzinin marka ve modelini, mevcut pompanın etiketindeki güç ve debi değerlerini ve bağlantı çapını bize iletirseniz uygun modeli birlikte belirleyebiliriz.'],
                ]],
            ],
            161 => [
                'meta_description' => ['Geri akışı önleyen entegre çekvalfli', 'Sumak DSM ve DSMT kendinden klapeli santrifüj pompalar: 2.2–3 HP, 2 inç giriş ve çıkış, 25 m³/h\'e kadar debi, 220 V ve 380 V modeller.'],
                'description' => ['<h2>Klapeli Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Klapeli Pompa Modelleri ve Fiyatları</h2>
<p><strong>Klapeli pompa</strong>, gövdesinde klape (tek yönlü valf) bulunan santrifüj yüzey pompasıdır. Klape, pompa durduğunda suyun geri akmasını engeller. Pompa kuru bir zemine kurulur ve suyu emerek çeker; depo, gölet veya kanaldan su aktarma ve sulama işlerinde kullanılır.</p>
<p>Bu kategorideki modellerin tamamı Sumak markalıdır.</p>
<h3>Sumak DSM ve DSMT Modelleri</h3>
<ul>
<li><strong>DSM 220/2 ve DSMT 220/2:</strong> 2.2 HP, 16–25 m³/h debi, 20 mss'ye kadar basma.</li>
<li><strong>DSM 300/2 ve DSMT 300/2:</strong> 3 HP, 16–25 m³/h debi, 30 mss'ye kadar basma.</li>
</ul>
<p>Tüm modellerde giriş ve çıkış 2 inç, emiş derinliği 6 m'dir. DSM modelleri 220 V monofaze, DSMT modelleri 380 V trifazedir. Model adındaki /2, 2 inç ağız çapını gösterir.</p>
<p>Elektriğin olmadığı yerler için aynı serinin dizel motorlu versiyonları <a href="{$so}/dizel-su-motorlari">dizel su motorları</a> sayfasındadır. Genel su transferi için <a href="{$sp}/santrifuj-pompalar">santrifüj pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Klapeli Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Emiş koşulu:</strong> Su seviyesi ile pompa arasındaki dikey mesafe 6 m'yi aşmamalıdır. Daha derindeki su için dalgıç pompa gerekir.</li>
<li><strong>Debi ve basma:</strong> Yüksek debi ve orta basma gereken işler içindir; 2.2 HP modeller 20 mss'ye, 3 HP modeller 30 mss'ye kadar basar.</li>
<li><strong>Emme hattı:</strong> Emme borusu pompa ağzından ince olmamalı, hava kaçırmayacak şekilde sızdırmaz bağlanmalıdır.</li>
<li><strong>İlk çalıştırma:</strong> Pompa gövdesi ilk çalıştırmadan önce suyla doldurulmalıdır; pompa susuz çalıştırılmamalıdır.</li>
<li><strong>Elektrik:</strong> 220 V monofaze şebekede DSM, 380 V trifaze şebekede DSMT modeller kullanılır.</li>
</ul>
HTML.self::CONTACT],
                'faq' => ['Klape ne işe yarar?', [
                    ['q' => 'Klape ne işe yarar?', 'a' => 'Klape, suyun yalnızca bir yönde akmasına izin veren valftir. Pompa durduğunda suyun geri akmasını engeller.'],
                    ['q' => 'Klapeli pompa dalgıç pompa mıdır?', 'a' => 'Hayır. Bu kategorideki Sumak DSM ve DSMT modelleri kuru zemine kurulan santrifüj yüzey pompalarıdır ve suyu 6 m\'ye kadar derinlikten emer. Suyun içine indirilecek pompa arıyorsanız <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompalar</a> sayfasına bakabilirsiniz.'],
                    ['q' => 'DSM ile DSMT arasındaki fark nedir?', 'a' => 'DSM modelleri 220 V monofaze, DSMT modelleri 380 V trifaze elektrikle çalışır. Aynı güçteki modellerin debi ve basma değerleri benzerdir.'],
                    ['q' => 'Klapeli pompa pis su basabilir mi?', 'a' => 'Bu modeller temiz veya az kirli su içindir. Katı parçalı atık su ve foseptik için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompalar</a> kullanılmalıdır.'],
                    ['q' => 'Klape tıkanırsa ne olur?', 'a' => 'Klape kapanmazsa su geri akar; açılmazsa pompa su basamaz. Emme tarafına süzgeç takmak ve klapeyi periyodik olarak temizlemek bu sorunu önler.'],
                ]],
            ],
            162 => [
                'description' => ['<h2>Dizel Su Motoru (Motorlu Pompa) Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Dizel Su Motoru (Motorlu Pompa) Modelleri ve Fiyatları</h2>
<p><strong>Dizel su motoru</strong> (dizel motopomp), su pompası ile dizel motorun ortak bir şasede birleştiği ve mazotla çalışan pompa ünitesidir. Elektrik bağlantısı olmayan tarla, bağ ve şantiyelerde, geçici su aktarma ve tahliye işlerinde kullanılır.</p>
<p>Bu kategorideki modellerin tamamı Sumak markalıdır.</p>
<h3>Sumak Dizel Motopomp Modelleri</h3>
<ul>
<li><strong>SMKT 750 D ve SMKT 750/2 D:</strong> Çift fanlı SMKT pompa serisiyle kurulan motopomplar. SMKT750 D modelinde 7.5 hp motor, 6 litre yakıt deposu ve 1½ inç çıkış bulunur.</li>
<li><strong>SMT 750/4 D ve SMT 1000/3 D:</strong> SMT santrifüj pompa serisiyle kurulan motopomplar.</li>
<li><strong>DSM 300/2 D ve DSMT750/3 D:</strong> Klapeli DSM pompa serisiyle kurulan motopomplar. DSMT750/3 D modelinde 7.5 hp motor, 6 litre yakıt deposu ve 3 inç çıkış bulunur.</li>
<li><strong>SYT 32/5 D:</strong> Yatay kademeli SYT pompa serisiyle kurulan motopomp; yüksek basma gereken işler içindir.</li>
</ul>
<p>Model adının baş kısmı pompanın serisini, sondaki D dizel motoru gösterir. Pompa serilerinin elektrikli versiyonları için <a href="{$sp}/santrifuj-pompalar/cift-fanli-santrifuj-pompa">çift fanlı santrifüj pompalar</a>, <a href="{$sp}/santrifuj-pompalar">santrifüj pompalar</a>, <a href="{$so}/klapeli-pompalar">klapeli pompalar</a> ve <a href="{$sp}/kademeli-pompalar/norm-tipi-yatay-kademeli">norm tipi yatay kademeli pompalar</a> sayfalarına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Dizel Su Motoru Nasıl Seçilir?</h3>
<ul>
<li><strong>İşin tipi:</strong> Yüksek debi ve düşük basma gereken sulama ve tahliye işlerinde tek veya çift fanlı santrifüj modeller, uzak veya yüksek noktaya su basmak için kademeli SYT modeli uygundur.</li>
<li><strong>Emiş koşulu:</strong> Motopomp da bir yüzey pompasıdır; su seviyesi ile pompa arasındaki dikey mesafe pratikte 6–7 metreyi aşmamalıdır.</li>
<li><strong>Debi ve basma yüksekliği:</strong> Sulanacak alanın ihtiyacı, suyun basılacağı yükseklik ve hortum veya boru uzunluğuna göre belirlenir.</li>
<li><strong>Çıkış çapı:</strong> Mevcut hortum veya boru çapıyla uyumlu olmalıdır.</li>
<li><strong>Kullanım süresi:</strong> Uzun süreli çalışmada yakıt deposunun kaç saat yeteceği ve yakıt ikmali planlanmalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/dizel-motopomp-secimi-rehberi">dizel motopomp seçimi rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Dizel su motoru ne kadar yakıt harcar?', [
                    ['q' => 'Dizel su motoru ne zaman tercih edilir?', 'a' => 'Elektrik bağlantısı olmayan tarla, bağ ve şantiyelerde, geçici su aktarma ve tahliye işlerinde tercih edilir. Elektrik bulunan ve sürekli çalışacak sistemlerde elektrikli pompa genellikle daha pratiktir.'],
                    ['q' => 'Dizel su motoru ne kadar yakıt harcar?', 'a' => 'Yakıt tüketimi motor gücüne ve pompanın ne kadar yüklendiğine bağlıdır. Güncel tüketim değeri için ürün sayfasındaki teknik bilgiler esas alınmalı veya bize danışılmalıdır.'],
                    ['q' => 'Model adındaki harf ve sayılar ne anlama gelir?', 'a' => 'Baştaki harfler pompa serisini (SMKT çift fanlı santrifüj, SMT santrifüj, DSM klapeli santrifüj, SYT yatay kademeli), sondaki D dizel motoru gösterir. Örneğin SMKT 750 D, çift fanlı SMKT pompalı dizel motopomptur.'],
                    ['q' => 'Dizel motopompun bakımı nasıl yapılır?', 'a' => 'Motor tarafında yağ, hava filtresi ve yakıt filtresi motor üreticisinin önerdiği aralıklarla kontrol edilir. Pompa tarafında salmastra ve çark kontrol edilir. Uzun süre kullanılmayacaksa pompa gövdesindeki su boşaltılmalı, donma riskine karşı önlem alınmalıdır.'],
                    ['q' => 'Dizel motopomp kuru çalışırsa ne olur?', 'a' => 'Mekanik salmastra pompalanan suyla soğur; susuz çalışmada kısa sürede zarar görür. İlk çalıştırmadan önce pompa gövdesi suyla doldurulmalı ve emme hattında hava kaçağı olmamalıdır.'],
                ]],
            ],
            164 => [
                'meta_description' => ['Bodrum, garaj ve bahçe çukurları için otomatik', 'Bodrum, garaj ve bahçe çukurlarında biriken yağmur suyu için Winpo WNP V 370 F ve V 750 F tahliye pompaları: 0.5–1 HP, 220 V, 13 mss\'ye kadar basma.'],
                'description' => ['<h2>Yağmur Suyu Tahliye Pompası Modelleri</h2>', <<<HTML
<h2>Yağmur Suyu Tahliye Pompası Modelleri</h2>
<p><strong>Yağmur suyu tahliye pompası</strong>, bodrum, garaj, bahçe çukuru ve yağmur suyu toplama haznelerinde biriken suyu dışarı atmak için kullanılan dalgıç pompadır. Pompa haznenin içine yerleştirilir ve suyu kanalizasyona veya uygun tahliye noktasına basar.</p>
<p>Bu kategoride Winpo markasının yağmur suyu tahliye pompaları yer alır.</p>
<h3>Winpo WNP V Modelleri</h3>
<ul>
<li><strong>Winpo WNP V 370 F:</strong> 0.5 HP, 8–10 m³/h debi, 10 mss'ye kadar basma, 1¼ inç çıkış, 5 m kablo, 220 V.</li>
<li><strong>Winpo WNP V 750 F:</strong> 1 HP, dakikada 180 litreye kadar debi, 13 mss'ye kadar basma, 2 inç çıkış, 30 mm'ye kadar parça geçişi, 5 m kablo.</li>
</ul>
<p>Daha yüksek debi veya basma için <a href="{$sd}/drenaj-dalgic-pompa">drenaj dalgıç pompalar</a>, çamurlu ve iri parçalı su için <a href="{$sd}/kirli-su-dalgic-pompa">kirli su dalgıç pompalar</a> sayfasına bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<HTML
<h3>Yağmur Suyu Tahliye Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Debi:</strong> Haznede biriken suyun ne kadar sürede boşaltılması gerektiğine göre seçilir. Yoğun yağış alan ve geniş alandan su toplayan haznelerde daha yüksek debili model gerekir.</li>
<li><strong>Basma yüksekliği:</strong> Hazne tabanından tahliye noktasına kadar olan dikey yükseklik ve hortum kayıpları pompanın basma değerini aşmamalıdır.</li>
<li><strong>Otomatik çalışma:</strong> Su seviyesi yükseldiğinde pompanın kendiliğinden devreye girmesi için şamandıralı model veya ayrı seviye şalteri kullanılmalıdır.</li>
<li><strong>Geri akış:</strong> Basma hattına çekvalf takılması, pompa durduğunda hattaki suyun hazneye geri dönmesini önler.</li>
<li><strong>Hazne:</strong> Pompanın rahat yerleşeceği, tortunun çökeceği ve şamandıranın takılmadan hareket edeceği büyüklükte olmalıdır.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/drenaj-dalgic-pompa-rehberi-bodrum-yagmur-suyu">bodrum ve yağmur suyu için drenaj pompası rehberi</a> yazımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Yağmur suyu pompası otomatik çalışır mı?', [
                    ['q' => 'Yağmur suyu pompası otomatik çalışır mı?', 'a' => 'Şamandıralı modeller su seviyesi yükseldiğinde kendiliğinden devreye girer ve su çekilince durur. Şamandırası olmayan pompalarda ayrı bir seviye şalteri kullanılmalıdır.'],
                    ['q' => 'Bodrum taşmasını önlemek için kaç pompa gerekir?', 'a' => 'Taşma riskinin yüksek olduğu yerlerde biri ana, biri yedek iki pompa önerilir. Elektrik kesintisi riskine karşı jeneratör veya kesintisiz güç kaynağı düşünülebilir.'],
                    ['q' => 'Yağmur suyu pompası çamurlu suda çalışır mı?', 'a' => 'Hafif tortulu suda çalışır; Winpo WNP V 750 F 30 mm\'ye kadar parçaları geçirir. Yoğun çamur veya iri parçalı su için <a href="/kategoriler/su-pompalari/dalgic-pompalar/kirli-su-dalgic-pompa">kirli su dalgıç pompa</a> seçilmelidir.'],
                    ['q' => 'Çekvalf gerekli mi?', 'a' => 'Önerilir. Çekvalf, pompa durduğunda basma hattındaki suyun hazneye geri dönmesini engeller; böylece pompa aynı suyu tekrar tekrar basmaz.'],
                    ['q' => 'Pompa haznesi nasıl olmalı?', 'a' => 'Pompanın rahat yerleşeceği, tortunun dipte çökeceği ve şamandıranın duvara takılmadan hareket edebileceği genişlikte olmalıdır. Hazne tabanı düzenli olarak temizlenmelidir.'],
                ]],
            ],
        ];
    }
};
