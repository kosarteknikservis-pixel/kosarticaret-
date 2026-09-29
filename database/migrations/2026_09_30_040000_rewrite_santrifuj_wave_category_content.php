<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SP = '/kategoriler/su-pompalari';

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
        $sf = self::SF;

        return [
            115 => [
                'meta_description' => ['Santrifüj, dalgıç, kademeli, sirkülasyon ve jet pompa dahil', 'Sumak, Pedrollo, Winpo ve Kaysu\'nun 1.000\'i aşkın su pompası modeli: dalgıç, santrifüj, kademeli, jet, preferikal ve sirkülasyon pompaları, teknik destek.'],
                'description' => ['<h2>Su Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Su pompası</strong>; kuyudan, depodan, şebekeden veya göletten aldığı suyu istenen noktaya ileten ya da tesisattaki basıncı artıran makinedir. Kuyudan ev için su çekmek, apartman tesisatını beslemek, tarla sulamak veya kalorifer devresinde suyu dolaştırmak farklı pompa tipleri gerektirir.</p>
<p>Bu kategoride Sumak, Pedrollo, Winpo ve Kaysu markalarının 1.000'i aşkın su pompası modeli yer alır. Doğru tipi seçmek için önce suyun nereden alınacağı, nereye ve hangi basınçla gönderileceği belirlenmelidir.</p>
<h3>Su Pompası Çeşitleri</h3>
<ul>
<li><strong>Dalgıç pompalar:</strong> Suyun içine indirilerek çalışır; derin kuyu, keson kuyu, drenaj, kirli su ve foseptik uygulamaları içindir. Bkz. <a href="{$sp}/dalgic-pompalar">dalgıç pompalar</a>.</li>
<li><strong>Santrifüj pompalar:</strong> Yüzeye kurulur; depo, gölet veya sığ kuyudan su çekip sulama, su transferi ve tesisat beslemesinde kullanılır. Emiş derinliği pratikte 6–7 metreyle sınırlıdır. Bkz. <a href="{$sf}">santrifüj pompalar</a>.</li>
<li><strong>Kademeli pompalar:</strong> Birden fazla çarkla yüksek basınç üretir; çok katlı bina besleme ve sanayi hatları içindir. Bkz. <a href="{$sp}/kademeli-pompalar">kademeli pompalar</a>.</li>
<li><strong>Jet pompalar:</strong> Ejektör sayesinde standart santrifüj pompaya göre daha derinden su emebilir; sığ kuyu ve bahçe sulaması için kullanılır. Bkz. <a href="{$sp}/jet-pompalar-derinden-emisli">jet pompalar</a>.</li>
<li><strong>Preferikal (sürtme fanlı) pompalar:</strong> Düşük debide yüksek basınç veren kompakt pompalardır; ev tipi küçük tesisatlarda kullanılır. Bkz. <a href="{$sp}/preferikal-pompalar-surtme-fanli">preferikal pompalar</a>.</li>
<li><strong>Sirkülasyon pompaları:</strong> Kalorifer ve yerden ısıtma gibi kapalı devrelerde suyu dolaştırır. Bkz. <a href="{$sp}/sirkulasyon-pompalari">sirkülasyon pompaları</a>.</li>
<li><strong>Özel amaçlı pompalar:</strong> Yangın, havuz, jakuzi, keson kuyu ve dizel motorlu pompalar. Bkz. <a href="{$sp}/ozel-amacli-pompalar">özel amaçlı pompalar</a>.</li>
</ul>
<p>Musluklarda sabit basınç gerekiyorsa pompa, basınç tankı ve basınç şalterinden oluşan <a href="/kategoriler/hidrofor-sistemleri">hidrofor sistemleri</a> daha uygun olabilir.</p>
<h3>Su Pompası Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca pompa tipi, motor gücü, debi ve basma yüksekliği, gövde malzemesi (döküm veya paslanmaz) ve elektrik beslemesi (220 V monofaze veya 380 V trifaze) belirler. Ayrıntılar için <a href="/blog/su-pompasi-fiyatlari-2026-rehberi">su pompası maliyetini belirleyen etkenler</a> yazımıza bakabilirsiniz. Markaya göre incelemek için <a href="/marka/sumak">Sumak</a>, <a href="/marka/pedrollo">Pedrollo</a>, <a href="/marka/winpo">Winpo</a> ve <a href="/marka/kaysu">Kaysu</a> sayfalarını ziyaret edebilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Su Pompası Nasıl Seçilir?</h3>', <<<HTML
<h3>Su Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Su kaynağı ve derinlik:</strong> Su seviyesi pompadan 6–7 metreden daha aşağıdaysa yüzey pompası emiş yapamaz; kuyu için dalgıç pompa veya derinden emişli jet pompa gerekir.</li>
<li><strong>Debi:</strong> Aynı anda kullanılacak musluk, sulama başlığı veya hattın ihtiyacına göre m³/h cinsinden belirlenir.</li>
<li><strong>Basma yüksekliği:</strong> Suyun çıkacağı en yüksek nokta, boru sürtünme kayıpları ve kullanım noktasında istenen basınç toplanarak bulunur. 1 bar yaklaşık 10 metre su sütununa karşılık gelir.</li>
<li><strong>Suyun türü:</strong> Temiz su, kumlu kuyu suyu, kirli su ve atık su farklı çark ve gövde ister. Tuzlu, klorlu veya kimyasal sıvılarda paslanmaz modeller seçilir.</li>
<li><strong>Elektrik:</strong> Evlerde genellikle 220 V monofaze, büyük güçlerde 380 V trifaze modeller kullanılır.</li>
</ul>
<h3>Hangi İş İçin Hangi Pompa?</h3>
<ul>
<li>Sondaj kuyusundan su çekmek için <a href="{$sp}/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a>.</li>
<li>Depo veya şebekeden eve basınçlı su için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidrofor</a>.</li>
<li>Gölet, depo veya kanaldan sulama için <a href="{$sf}">santrifüj pompa</a>.</li>
<li>Çok katlı binaya su basmak için <a href="{$sp}/kademeli-pompalar">kademeli pompa</a> veya <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a>.</li>
<li>Bodrum ve çukurlardaki suyu boşaltmak için <a href="{$sp}/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompa</a>.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/su-pompasi-cesitleri-nasil-secilir">su pompası çeşitleri ve seçimi</a> ile <a href="/blog/pompa-debi-basma-yuksekligi-hesabi">debi ve basma yüksekliği hesabı</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Su pompası seçerken nelere dikkat edilmeli?', [
                    ['q' => 'Su pompası çeşitleri nelerdir?', 'a' => 'Başlıca tipler dalgıç, santrifüj, kademeli, jet, preferikal ve sirkülasyon pompalarıdır. Dalgıç pompalar suyun içinde, santrifüj, jet ve preferikal pompalar yüzeyde çalışır. Kademeli pompalar yüksek basınç, sirkülasyon pompaları ise kapalı ısıtma devreleri içindir.'],
                    ['q' => 'Santrifüj pompa ile dalgıç pompa arasındaki fark nedir?', 'a' => 'Santrifüj pompa yüzeye kurulur ve suyu emerek çeker; emiş derinliği pratikte 6–7 metreyle sınırlıdır. Dalgıç pompa ise suyun içine indirilir ve suyu iterek basar; bu yüzden derin kuyularda ve su altındaki uygulamalarda kullanılır.'],
                    ['q' => 'Ev için hangi su pompası seçilmeli?', 'a' => 'Şebeke basıncı yetersizse depo ile birlikte hidrofor kullanılır. Su kuyudan alınıyorsa su seviyesine göre jet pompa veya dalgıç pompa seçilir. Bahçe sulaması için santrifüj veya jet pompa yeterli olabilir. Ayrıntılar için <a href="/blog/ev-icin-su-pompasi-secimi-rehberi">ev için su pompası seçimi</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Pompanın basma yüksekliği nasıl hesaplanır?', 'a' => 'Su seviyesi ile en yüksek kullanım noktası arasındaki dikey mesafeye boru, dirsek ve vanalardaki sürtünme kayıpları ile kullanım noktasında istenen basınç eklenir. 1 bar yaklaşık 10 metre su sütunudur; örneğin 15 metre yükseklik ve 2 bar kullanım basıncı için sürtünme kayıplarıyla birlikte 35 metrenin üzerinde basma gerekir.'],
                    ['q' => 'Su pompası kuru çalışırsa ne olur?', 'a' => 'Birçok pompada mekanik salmastra pompalanan suyla soğur. Susuz çalışan pompada salmastra kısa sürede zarar görür ve pompa sızdırmaya başlar. Su kesilme riski olan depo ve kuyularda şamandıra veya kuru çalışma koruması kullanılması önerilir.'],
                ]],
            ],
            132 => [
                'description' => ['<h2>Santrifüj Pompa Çeşitleri ve Fiyatları</h2>', <<<HTML
<h2>Santrifüj Pompa Çeşitleri ve Fiyatları</h2>
<p><strong>Santrifüj pompa</strong>, motorun döndürdüğü çarkın (fanın) suya hız kazandırdığı ve gövdenin bu hızı basınca çevirdiği yüzey pompasıdır. Depo, gölet, kanal veya sığ kuyudan su çekip sulama, su transferi, tesisat besleme ve sanayi proseslerinde kullanılır. Suyun içine değil kuru bir zemine kurulur; emiş derinliği pratikte 6–7 metreyle sınırlıdır.</p>
<p>Bu kategoride Pedrollo, Sumak ve Winpo markalarının tek fanlı, çift fanlı, flanşlı, paslanmaz ve salyangoz santrifüj pompaları yer alır.</p>
<h3>Santrifüj Pompa Türleri</h3>
<ul>
<li><strong>Tek fanlı santrifüj pompalar:</strong> Pedrollo CP ve HF, Sumak SM ve SMT, Winpo WNP 158 serileri; 0.5–15 HP. Bahçe ve tarla sulaması, su transferi ve tesisat beslemesi içindir. Bkz. <a href="{$sf}/tek-fanli-santrifuj-pompa">tek fanlı santrifüj pompalar</a>.</li>
<li><strong>Çift fanlı santrifüj pompalar:</strong> Pedrollo 2CP ile Sumak SMK ve SMKT serileri; iki çark seri çalıştığı için 112 mss'ye kadar basma sağlar. Bkz. <a href="{$sf}/cift-fanli-santrifuj-pompa">çift fanlı santrifüj pompalar</a>.</li>
<li><strong>Sulama için flanşlı santrifüj pompalar:</strong> Pedrollo F ve Fm serisi; DN32–DN80 çıkışlı, 60 HP'ye kadar modeller. Bkz. <a href="{$sf}/santrifuj-pompalar-sulama">sulama santrifüj pompaları</a>.</li>
<li><strong>Paslanmaz ve kimyasal pompalar:</strong> AISI 304 veya AISI 316 paslanmaz gövdeli Pedrollo, Sumak SMINOX ve Winpo modelleri; havuz, arıtma, gıda ve malzemeyle uyumlu kimyasal sıvılar içindir. Bkz. <a href="{$sf}/paslanmaz-pompalar-kimyasal">paslanmaz pompalar</a>.</li>
<li><strong>Salyangoz pompalar:</strong> Sumak SMT 250 serisi; 7.5–55 kW motor gücüyle yüksek debi gereken sulama ve transfer işleri içindir. Bkz. <a href="{$sf}/salyangoz-pompalar-bol-su-veren">salyangoz pompalar</a>.</li>
</ul>
<h3>Santrifüj Pompa Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca motor gücü, fan sayısı, gövde malzemesi (döküm veya paslanmaz), ağız çapı ve elektrik beslemesi belirler. Ayrıntılar için <a href="/blog/santrifuj-pompa-fiyatlari-2026-rehberi">santrifüj pompa maliyetini belirleyen etkenler</a> yazımıza bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Santrifüj Pompa Seçim Kriterleri</h3>', <<<'HTML'
<h3>Santrifüj Pompa Nasıl Seçilir?</h3>
<ul>
<li><strong>Emiş koşulu:</strong> Pompa sudan yukarıdaysa su seviyesi ile pompa arasındaki dikey mesafe ürün sayfasındaki emiş değerini aşmamalıdır; örneğin Pedrollo CP ve F serilerinde 7 m, Sumak SM ve SMT serilerinde 6 m'dir. Daha derindeki su için jet pompa veya dalgıç pompa gerekir.</li>
<li><strong>Debi ve basma:</strong> İhtiyaç duyulan debi ve basma yüksekliği pompa eğrisinin orta bölgesine denk gelmelidir; eğrinin uç noktalarında çalışan pompa verimsiz çalışır.</li>
<li><strong>Fan sayısı:</strong> Gereken basma tek fanlı modellerin aralığını aşıyorsa çift fanlı veya kademeli pompa seçilir.</li>
<li><strong>Suyun türü:</strong> Temiz su için döküm gövde yeterlidir. Tuzlu, klorlu veya kimyasal sıvılarda paslanmaz gövde seçilir ve sıvının pompa malzemesiyle uyumu kontrol edilir.</li>
<li><strong>Elektrik:</strong> Pedrollo'da model adındaki "m" harfi (CPm, 2CPm, HFm, Fm) 220 V monofaze versiyonu gösterir. Sumak'ta SM ve SMK 220 V, SMT ve SMKT 380 V'tur.</li>
</ul>
<p>Standart santrifüj pompalar kendinden emişli değildir: ilk çalıştırmada gövde ve emiş borusu suyla doldurulmalı, emiş ucuna dip klapesi takılmalıdır. Ayrıntılar için <a href="/blog/santrifuj-pompa-emme-priming-rehberi">santrifüj pompa emme ve ön dolum rehberi</a> ile <a href="/blog/santrifuj-pompa-turleri-secim-rehberi">santrifüj pompa türleri ve seçimi</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Santrifüj pompa hangi uygulamalar için idealdir?', [
                    ['q' => 'Santrifüj pompa ne işe yarar?', 'a' => 'Depo, gölet, kanal veya sığ kuyudaki suyu çekip başka bir noktaya basar. Bahçe ve tarla sulaması, su transferi, tesisat besleme, soğutma devreleri ve sanayi proseslerinde kullanılır. Yüzeye kurulduğu için bakımı dalgıç pompaya göre kolaydır.'],
                    ['q' => 'Santrifüj pompa kaç metreden su çeker?', 'a' => 'Atmosfer basıncı nedeniyle teorik emiş sınırı yaklaşık 10 metredir; boru kayıpları ve su sıcaklığı bu değeri düşürür. Bu kategorideki Pedrollo ve Sumak modellerinin emiş değeri 6–7 metredir. Su daha derindeyse derinden emişli jet pompa veya dalgıç pompa kullanılmalıdır.'],
                    ['q' => 'Santrifüj pompa neden su basmaz?', 'a' => 'En sık nedenler gövdenin ve emiş borusunun suyla doldurulmaması, dip klapesinin kaçırması, emiş hattındaki hava kaçağı ve emiş derinliğinin fazla olmasıdır. Trifaze pompalarda motorun ters dönmesi de basmamaya yol açar. Ayrıntılar için <a href="/blog/santrifuj-pompa-ariza-bakim-rehberi">santrifüj pompa arıza ve bakım rehberi</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Santrifüj pompa kuruda çalışırsa ne olur?', 'a' => 'Mekanik salmastra pompalanan suyla soğur ve yağlanır. Susuz çalışan pompada salmastra kısa sürede zarar görür, pompa sızdırmaya başlar. Su kesilme riski olan depo ve göletlerde şamandıra veya kuru çalışma koruması kullanılmalıdır.'],
                    ['q' => 'Santrifüj pompa mı, kademeli pompa mı seçilmeli?', 'a' => 'Tek fanlı santrifüj pompalar yüksek debi ve orta basma gereken sulama ve transfer işlerine uygundur. Daha düşük debide yüksek basınç gerekiyorsa, örneğin çok katlı bina beslemesinde, kademeli pompa tercih edilir. Ayrıntılar için <a href="/blog/kademeli-pompa-mi-santrifuj-pompa-mi">kademeli pompa mı, santrifüj pompa mı</a> yazımıza bakabilirsiniz.'],
                ]],
            ],
            133 => [
                'description' => ['<h2>Tek Fanlı Santrifüj Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Tek Fanlı Santrifüj Pompa Modelleri ve Fiyatları</h2>
<p><strong>Tek fanlı santrifüj pompa</strong>, tek bir çarkla çalışan yüzey pompasıdır. Depo, gölet veya sığ kuyudan su çekip bahçe ve tarla sulaması, su transferi ve tesisat beslemesinde kullanılır. Yapısı basit olduğu için bakımı kolaydır.</p>
<p>Bu kategoride şu seriler yer alır:</p>
<ul>
<li><strong>Pedrollo CP ve CPm:</strong> 0.5–15 HP; 23–77 mss basma ve 4.2–54 m³/h debi; 1"–2" ağız.</li>
<li><strong>Pedrollo HF ve HFm:</strong> 1.5–5.5 HP; 14.7–24.5 mss basma ve 36–72 m³/h debi; 2"–4" ağız. Düşük basma ve yüksek debi gereken sulama ve transfer işleri içindir.</li>
<li><strong>Sumak SM ve SMT:</strong> 0.85–15 HP; 1"–4" ağız ve 54 m'ye kadar basma. Açık çarklı -A modelleri de bulunur.</li>
<li><strong>Winpo WNP 158:</strong> 1 HP, 37 mss ve 6.6 m³/h; 220 V ve 380 V versiyonlar.</li>
<li><strong>Kaysu HCPF-70:</strong> 2 HP, 24 mSS ve 33 m³/h; 2" ağız.</li>
</ul>
<p>Daha yüksek basınç için <a href="{$sf}/cift-fanli-santrifuj-pompa">çift fanlı santrifüj pompalara</a> veya <a href="{$sp}/kademeli-pompalar">kademeli pompalara</a>, flanşlı ve yüksek debili modeller için <a href="{$sf}/santrifuj-pompalar-sulama">sulama santrifüj pompalarına</a>, tüm seriler için <a href="{$sf}">santrifüj pompalar</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Tek Fanlı Santrifüj Pompa Seçimi</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Model adındaki mss değeri pompanın debi sıfırken ulaştığı en yüksek basmadır. Kullanım noktasında gereken basma bu değerin belirgin şekilde altında kalmalıdır.</li>
<li><strong>Debi ve ağız çapı:</strong> 1" ağızlı modeller ev ve bahçe kullanımı, 2"–4" ağızlı Pedrollo HF ve Sumak /2, /3, /4 modelleri yüksek debili sulama ve transfer içindir.</li>
<li><strong>Emiş:</strong> Pedrollo modellerinde 7 m, Sumak, Winpo ve Kaysu modellerinde 6 m emiş değeri verilir. Emiş borusu kısa tutulmalı ve ucuna dip klapesi takılmalıdır.</li>
<li><strong>Elektrik:</strong> Pedrollo'da adında "m" bulunan modeller (CPm, HFm) ve Sumak SM modelleri 220 V monofazedir; Sumak SMT modelleri 380 V trifazedir.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/tek-fanli-santrifuj-pompa-rehberi">tek fanlı santrifüj pompa rehberi</a> ve <a href="/blog/santrifuj-pompa-emme-priming-rehberi">santrifüj pompa emme ve ön dolum rehberi</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Tek fanlı santrifüj pompa ne kadar yüksekliğe su basabilir?', [
                    ['q' => 'Tek fanlı santrifüj pompa ne kadar yükseğe su basar?', 'a' => 'Bu kategorideki modellerin en yüksek basma değeri yaklaşık 15 mss ile 77 mss arasındadır. Bu değer debi sıfırken ölçülür; pompa daha fazla su verdikçe basma yüksekliği düşer. Bu yüzden seçimde pompa eğrisi dikkate alınmalıdır.'],
                    ['q' => 'Model adındaki harf ve sayılar ne anlama gelir?', 'a' => 'Pedrollo\'da adında "m" bulunan modeller (CPm, HFm) 220 V monofazedir. Sumak\'ta SM 220 V, SMT 380 V versiyondur; /2, /3 ve /4 ekleri ağız çapını inç olarak gösterir, -A eki açık çarklı modeli belirtir. Ürün adlarındaki mss ve m³/h değerleri en yüksek basma ve debidir.'],
                    ['q' => 'Santrifüj pompa neden ön dolum (priming) ister?', 'a' => 'Standart santrifüj pompalar havayı emerek suyu yukarı çekemez; çalışmadan önce gövdenin ve emiş borusunun suyla dolu olması gerekir. Emiş ucundaki dip klapesi pompa durduğunda suyun geri kaçmasını önler. Klape kaçırıyorsa pompa her çalıştırmada yeniden doldurulmak zorunda kalır.'],
                    ['q' => 'Tek fanlı mı, çift fanlı mı seçilmeli?', 'a' => 'Gereken basma yüksekliği tek fanlı modellerin aralığında kalıyorsa tek fanlı pompa hem daha ekonomik hem de daha yüksek debilidir. Suyun yüksek bir noktaya veya uzun bir hatta basılması gerekiyorsa çift fanlı model seçilir.'],
                    ['q' => 'Paslanmaz mı, döküm gövdeli santrifüj pompa mı?', 'a' => 'Temiz kuyu, şebeke ve depo suyunda döküm gövdeli pompalar yaygın olarak kullanılır. Tuzlu, klorlu veya kimyasal içeren sularda paslanmaz gövde gerekir. Bu uygulamalar için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal">paslanmaz pompalar</a> kategorisine bakabilirsiniz.'],
                ]],
            ],
            141 => [
                'meta_description' => ['Orta debi ve yüksek basınç gerektiren', 'Pedrollo 2CP ve Sumak SMK, SMKT çift fanlı santrifüj pompalar: 112 mss\'ye kadar basma, 220 V monofaze ve 380 V trifaze modeller.'],
                'description' => ['<h2>Çift Fanlı Santrifüj Pompa Modelleri</h2>', <<<HTML
<h2>Çift Fanlı Santrifüj Pompa Modelleri</h2>
<p><strong>Çift fanlı santrifüj pompa</strong>, aynı gövde içinde seri çalışan iki çarkı sayesinde tek fanlı modellere göre daha yüksek basma sağlayan yüzey pompasıdır. Suyu yüksek bir noktaya veya uzun bir hat boyunca basmak, yağmurlama sulama ve tesisat basınçlandırma gibi orta debi ve yüksek basınç gereken işlerde kullanılır.</p>
<p>Bu kategoride iki seri yer alır:</p>
<ul>
<li><strong>Pedrollo 2CP ve 2CPm:</strong> 1–15 HP; 42–112 mss basma ve 6–27 m³/h debi; 7 m emiş. 2CPm modelleri 220 V monofazedir.</li>
<li><strong>Sumak SMK ve SMKT:</strong> 2.2–7.5 HP; 82 m'ye kadar basma; 6 m emiş. SMK modelleri 220 V, SMKT modelleri 380 V'tur. Adında /2 bulunan modeller 2" ağızlı, daha yüksek debili versiyonlardır.</li>
</ul>
<p>Yüksek debi ve daha düşük basma için <a href="{$sf}/tek-fanli-santrifuj-pompa">tek fanlı santrifüj pompalara</a>, sürekli yüksek basınç gereken bina tesisatları için <a href="{$sp}/kademeli-pompalar">kademeli pompalara</a>, tüm seriler için <a href="{$sf}">santrifüj pompalar</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Çift Fanlı Santrifüj Pompa Seçimi</h3>
<ul>
<li><strong>Basma yüksekliği:</strong> Kot farkı, boru kayıpları ve kullanım noktasındaki basınç toplanır. Ürün adındaki mss değeri debi sıfırkenki en yüksek basmadır; gereken değer bunun altında kalmalıdır.</li>
<li><strong>Debi:</strong> Çift fanlı pompalar yüksek basınç için tasarlandığından debileri aynı güçteki tek fanlı pompalardan düşüktür. Yüksek debi gerekiyorsa büyük ağızlı modeller veya tek fanlı pompa değerlendirilmelidir.</li>
<li><strong>Emiş:</strong> Pedrollo 2CP'de 7 m, Sumak SMK ve SMKT'de 6 m emiş değeri verilir; su daha derindeyse dalgıç pompa gerekir.</li>
<li><strong>Elektrik:</strong> Evde yalnızca 220 V varsa 2CPm veya SMK, trifaze hat varsa 2CP veya SMKT modelleri seçilir.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/cift-fanli-santrifuj-pompa-rehberi">çift fanlı santrifüj pompa rehberi</a> yazımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Çift fanlı pompa ne avantaj sağlar?', [
                    ['q' => 'Çift fanlı santrifüj pompa ne işe yarar?', 'a' => 'İki çark seri çalıştığı için su ikinci çarka basınçlı girer ve toplam basma yüksekliği artar. Tek fanlı pompanın yetmediği, suyun yüksek bir noktaya veya uzun bir hatta basılması gereken işlerde kullanılır.'],
                    ['q' => 'Çift fanlı pompa kaç metreye su basar?', 'a' => 'Bu kategorideki Pedrollo 2CP modelleri 42–112 mss, Sumak SMK ve SMKT modelleri 82 m\'ye kadar basma değerine sahiptir. Bu değerler debi sıfırken ölçülür; pompa su verdikçe basma yüksekliği düşer.'],
                    ['q' => 'Çift fanlı pompa daha çok elektrik harcar mı?', 'a' => 'Aynı debide daha yüksek basınç ürettiği için genellikle daha büyük motor gücü gerekir. Ancak ihtiyaçtan büyük pompa seçmek de enerji israfına yol açar; doğru model gereken debi ve basmaya göre belirlenmelidir.'],
                    ['q' => 'Çift fanlı pompa ile hidrofor yapılabilir mi?', 'a' => 'Pompa, basınç tankı ve basınç şalteriyle birlikte kurularak hidrofor olarak kullanılabilir. Konutta sabit basınç ve sessiz çalışma öncelikliyse hazır <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidroforlar</a> daha pratik bir çözümdür.'],
                    ['q' => 'Çift fanlı mı, kademeli pompa mı seçilmeli?', 'a' => 'Bahçe, tarla ve tek noktaya su basma gibi aralıklı işlerde çift fanlı pompa yeterlidir. Çok katlı bina beslemesi gibi sürekli ve daha yüksek basınç gereken uygulamalarda kademeli pompalar tercih edilir.'],
                ]],
            ],
            144 => [
                'description' => ['<h2>Sulama İçin Santrifüj Pompa Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Sulama İçin Santrifüj Pompa Modelleri ve Fiyatları</h2>
<p>Bu kategoride tarla, bahçe ve sera sulaması ile yüksek debili su transferi için <strong>Pedrollo F ve Fm serisi flanşlı santrifüj pompalar</strong> yer alır. Gölet, kanal, havuz, depo veya sığ kuyudan su çekerek yağmurlama ve damla sulama hatlarını besler.</p>
<ul>
<li><strong>F 32:</strong> 1¼" (32 mm) çıkış; 3–20 HP; 31–98 mss basma ve 24–30 m³/h debi.</li>
<li><strong>F 40:</strong> 1½" (40 mm) çıkış; 3–20 HP; 27–88 mss basma ve 36–42 m³/h debi.</li>
<li><strong>F 50:</strong> 2" (50 mm) çıkış; 3–30 HP; 18.5–95 mss basma ve 54–108 m³/h debi.</li>
<li><strong>F 65:</strong> 2½" (65 mm) çıkış; 5.5–60 HP.</li>
<li><strong>F 80:</strong> 3" (80 mm) çıkış; 15–50 HP; serinin en yüksek debili grubu.</li>
</ul>
<p>Model adındaki ilk sayı çıkış ağzı çapını (mm), ikinci sayı nominal çark çapını gösterir; çark çapı büyüdükçe basma yüksekliği artar. Fm 32/160B ve Fm 40/160C 220 V monofaze, diğer F modelleri 380 V trifazedir. Adında -I bulunan F 50/160 ve F 65/125 modelleri paslanmaz versiyondur.</p>
<p>Daha yüksek motor gücü için <a href="{$sf}/salyangoz-pompalar-bol-su-veren">salyangoz pompalara</a>, elektrik olmayan araziler için <a href="{$sp}/ozel-amacli-pompalar/dizel-su-motorlari">dizel su motorlarına</a>, derin kuyudan sulama için <a href="{$sp}/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompalara</a>, tüm seriler için <a href="{$sf}">santrifüj pompalar</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Sulama Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Sulama yöntemi:</strong> Damla sulama düşük debi ve düşük basınçla çalışır; yağmurlama başlıkları ve tabancalar daha yüksek basınç ister. Başlık üreticisinin verdiği çalışma basıncı esas alınmalıdır.</li>
<li><strong>Debi:</strong> Aynı anda çalışacak başlık veya damlatıcıların debileri toplanır.</li>
<li><strong>Basma yüksekliği:</strong> Su kaynağı ile arazinin en yüksek noktası arasındaki kot farkı, boru kayıpları ve başlık çalışma basıncı toplanır; 1 bar yaklaşık 10 metredir.</li>
<li><strong>Emiş:</strong> F serisinde emiş değeri 7 metredir. Su seviyesi daha derindeyse dalgıç pompa kullanılmalıdır.</li>
<li><strong>Elektrik:</strong> Trifaze hat yoksa Fm modelleri veya dizel su motorları değerlendirilir.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/sulama-santrifuj-pompasi-rehberi">sulama santrifüj pompası rehberi</a> ve <a href="/blog/tarimsal-sulama-pompasi-secimi-rehberi">tarımsal sulama pompası seçimi</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Tarım sulaması için hangi tip pompa daha uygundur?', [
                    ['q' => 'Tarım sulaması için hangi tip pompa daha uygundur?', 'a' => 'Seçim su kaynağına göre yapılır. Gölet, kanal ve depodan sulamada flanşlı santrifüj veya salyangoz pompa, sığ kuyularda jet pompa, derin kuyularda derin kuyu dalgıç pompa kullanılır. Elektriğin olmadığı arazilerde dizel su motoru tercih edilir.'],
                    ['q' => '1 dönüm için kaç m³/h debi gerekir?', 'a' => 'Sulama yöntemine ve başlık seçimine göre değişir. Örneğin saatte 10 mm su uygulayan bir yağmurlama sistemi 1 dönüm (1.000 m²) için yaklaşık 10 m³/h debi ister. Damla sulamada ihtiyaç çok daha düşüktür. Kesin değer, aynı anda çalışan başlık veya damlatıcıların debileri toplanarak bulunur.'],
                    ['q' => 'Sulama pompası sezon sonunda nasıl saklanmalı?', 'a' => 'Pompa temiz suyla çalıştırılarak yıkanmalı, ardından gövdedeki su boşaltma tapasından tamamen boşaltılmalıdır; gövdede kalan su donarsa gövdeyi çatlatabilir. Emme ve basma ağızları kapatılıp pompa kuru ve korunaklı bir yerde saklanmalıdır.'],
                    ['q' => 'Sulama pompası invertörle kullanılabilir mi?', 'a' => 'Trifaze pompalar, motor gücüne uygun bir invertörle çalıştırılabilir. İnvertör devri ihtiyaca göre ayarlar; aynı anda farklı sayıda hattın açıldığı sistemlerde enerji tüketimini azaltır ve yumuşak kalkış sağlar. İnvertör seçimi motor gücü ve akımına göre yapılmalıdır.'],
                    ['q' => 'Sulama borusu çapı nasıl seçilir?', 'a' => 'Borudaki su hızı genellikle 1–2 m/s aralığında tutulur; daha yüksek hız sürtünme kaybını artırır. Örneğin 10 m³/h debi ve 1.5 m/s hız için yaklaşık 49 mm iç çap gerekir; bu da polietilen boruda 63 mm dış çaplı boruya karşılık gelir. Uzun hatlarda boru kayıpları ayrıca hesaplanmalıdır.'],
                ]],
            ],
            158 => [
                'description' => ['<h2>Salyangoz Pompa (Volut Pompası) Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Salyangoz Pompa (Volut Pompası) Modelleri ve Fiyatları</h2>
<p><strong>Salyangoz pompa</strong>, çarkın çevresini saran salyangoz (volut) biçimli gövdesinden adını alan yatay milli santrifüj pompadır. Bol su veren pompa olarak da bilinir; tarla sulaması, sanayide su transferi ve inşaat sahalarında su boşaltma gibi yüksek debi gereken işlerde kullanılır.</p>
<p>Bu kategorideki modellerin tamamı <strong>Sumak SMT 250 serisi</strong> motorlu salyangoz pompadır:</p>
<ul>
<li><strong>SMT 250/32:</strong> 7.5, 11 ve 15 kW</li>
<li><strong>SMT 250/40:</strong> 11, 15, 18.5, 22 ve 30 kW</li>
<li><strong>SMT 250/50:</strong> 18.5, 22, 30 ve 37 kW</li>
<li><strong>SMT 250/65:</strong> 22, 30, 37, 45 ve 55 kW</li>
<li><strong>SMT 250/80:</strong> 37, 45 ve 55 kW</li>
<li><strong>SMT 250/100:</strong> 45 ve 55 kW</li>
</ul>
<p>Tüm modeller 2900 d/d devirli, 380 V trifaze motorludur ve 6 metre emiş değerine sahiptir.</p>
<p>Daha küçük debiler için <a href="{$sf}/tek-fanli-santrifuj-pompa">tek fanlı santrifüj pompalara</a> ve <a href="{$sf}/santrifuj-pompalar-sulama">sulama santrifüj pompalarına</a>, elektrik olmayan araziler için <a href="{$sp}/ozel-amacli-pompalar/dizel-su-motorlari">dizel su motorlarına</a>, tüm seriler için <a href="{$sf}">santrifüj pompalar</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Salyangoz Pompa Seçimi</h3>
<ul>
<li><strong>Debi ve basma:</strong> Seçim, ihtiyaç duyulan debi ve basma yüksekliğinin pompa eğrisiyle eşleştirilmesiyle yapılır. Aynı gövdenin birden fazla motor gücü seçeneği vardır; ihtiyaçtan büyük motor enerji tüketimini artırır. Değerlerinizi bize iletirseniz uygun modeli birlikte belirleyebiliriz.</li>
<li><strong>Emiş hattı:</strong> Emiş değeri 6 metredir. Emiş borusu kısa ve düz tutulmalı, pompa emiş ağzından dar olmamalı ve ucuna dip klapesi takılmalıdır. İlk çalıştırmada gövde ve emiş borusu suyla doldurulmalıdır.</li>
<li><strong>Elektrik ve yol verme:</strong> Büyük güçlü trifaze motorlarda genellikle yıldız-üçgen veya yumuşak yol verici kullanılır; pano ve kablo kesiti motor gücüne göre seçilmelidir.</li>
<li><strong>Montaj:</strong> Pompa sağlam bir beton kaideye sabitlenmeli, boru ağırlığı pompa ağızlarına yük bindirmemelidir.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/salyangoz-pompa-rehberi">salyangoz pompa rehberi</a> yazımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Salyangoz pompa neden bu kadar büyük debi sağlayabilir?', [
                    ['q' => 'Salyangoz pompa nedir?', 'a' => 'Çarkın çevresini saran salyangoz biçimli gövdesiyle suyun hızını basınca çeviren yatay milli santrifüj pompadır. Genellikle yüksek motor gücüyle çalışır ve tarım, sanayi ve inşaat sahalarında büyük miktarda su transferi için kullanılır.'],
                    ['q' => 'Salyangoz pompa ile normal santrifüj pompa arasındaki fark nedir?', 'a' => 'Salyangoz pompa da bir santrifüj pompadır; fark boyut ve kullanım amacındadır. Bu kategorideki Sumak SMT 250 modelleri 7.5–55 kW motor gücüyle büyük ölçekli işler için üretilir. Ev, bahçe ve küçük sulama işlerinde tek fanlı santrifüj pompalar yeterlidir.'],
                    ['q' => 'Salyangoz pompa kaç metreden su çeker?', 'a' => 'Bu kategorideki modellerin emiş değeri 6 metredir. Emiş borusunun uzunluğu, dirsek sayısı ve su sıcaklığı gerçek emiş kapasitesini düşürür. Su seviyesi daha derindeyse dalgıç pompa gerekir.'],
                    ['q' => 'Salyangoz pompa yangın sistemlerinde kullanılabilir mi?', 'a' => 'Yangın söndürme tesisatında kullanılacak pompanın proje ve ilgili yangın mevzuatı şartlarına uygun olması gerekir. Bu uygulamalar için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari">yangın pompaları</a> kategorisine bakmanızı öneririz.'],
                    ['q' => 'Salyangoz pompanın bakımında nelere dikkat edilmeli?', 'a' => 'Salmastra bölgesindeki sızıntı, rulman sesi, titreşim ve motor akımı düzenli kontrol edilmelidir. Emiş hattındaki hava kaçağı ve dip klapesinin durumu performansı doğrudan etkiler. Sezon dışında pompa boşaltılarak donmaya karşı korunmalıdır.'],
                ]],
            ],
        ];
    }
};
