<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const CAT = '/kategoriler/su-pompalari/sirkulasyon-pompalari';

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
        $cat = self::CAT;

        return [
            149 => [
                'description' => ['<h2>Sirkülasyon Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Sirkülasyon Pompası Modelleri ve Fiyatları</h2>
<p><strong>Sirkülasyon pompası</strong>; kalorifer, yerden ısıtma, fan coil ve soğutma gibi kapalı devre tesisatlarda suyu kazan veya ısı kaynağı ile radyatörler arasında sürekli dolaştıran pompadır. Suyu yukarı basmak ya da şebeke basıncını artırmak için değil, devredeki suyu döndürmek için kullanılır. Bu yüzden basma yüksekliği düşük, çalışma süresi uzundur.</p>
<p>Bu kategorideki modellerin tamamı Sumak üretimidir. Konut tipi rekorlu pompalardan büyük binalar için flanşlı ve inline pompalara, 90 °C'ye kadar sıcak su basan pompalara kadar farklı seriler bulunur.</p>
<h3>Sirkülasyon Pompası Çeşitleri</h3>
<ul>
<li><strong>Rekorlu (dişli) sirkülasyon pompaları:</strong> Sumak SSP 25 ve SSP 32 INV; 1" ve 1¼" rakorlu, frekans konvertörlü modeller. Daire, müstakil ev ve villa kalorifer ile yerden ısıtma devreleri içindir. Bkz. <a href="{$cat}/rekorlu-disli-sirkulasyon-pompalari">rekorlu dişli sirkülasyon pompaları</a>.</li>
<li><strong>Flanşlı sirkülasyon pompaları:</strong> Sumak SSP 40, 50 ve 65 INV; DN40–DN65 flanşlı, 46 m³/h'e kadar debili frekans konvertörlü modeller. Apartman, okul ve iş yerlerinin merkezi ısıtma devreleri içindir. Bkz. <a href="{$cat}/flansli-sirkulasyon-pompalari">flanşlı sirkülasyon pompaları</a>.</li>
<li><strong>Inline pompalar:</strong> Sumak SML 160/65 ve 160/80; 1.5–25 HP ve 120 m³/h'e kadar debiyle büyük bina ve tesislerin ısıtma, soğutma ve su transferi hatları içindir. Bkz. <a href="{$cat}/inline-sirkulasyon-pompalari">inline sirkülasyon pompaları</a>.</li>
<li><strong>Sıcak su pompaları:</strong> Sumak -S serisi; 90 °C'ye kadar sıcak suyu basan preferikal, santrifüj ve çift kademeli pompalar. Kazan dairesi, boyler ve güneş enerjisi hatlarında kullanılır. Bkz. <a href="{$cat}/sicak-su-pompalari">sıcak su pompaları</a>.</li>
</ul>
<h3>Sirkülasyon Pompası Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca bağlantı tipi (rekorlu veya flanşlı), debi ve basma yüksekliği, motor gücü ve frekans konvertörü belirler. Konut tipi rekorlu bir pompa ile büyük bir bina için flanşlı ya da inline pompa arasında bu nedenle belirgin fark vardır. Ayrıntılar için <a href="/blog/sirkulasyon-pompa-fiyatlari-2026-rehberi">sirkülasyon pompası maliyetini belirleyen etkenler</a> yazımıza bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['<h3>Sirkülasyon Pompası Seçim Rehberi</h3>', <<<'HTML'
<h3>Sirkülasyon Pompası Nasıl Seçilir?</h3>
<p>Doğru pompa için tesisatın tipi, gereken debi, devrenin direnci (basma yüksekliği) ve bağlantı ölçüsü birlikte değerlendirilir.</p>
<ul>
<li><strong>Tesisat tipi:</strong> Radyatörlü kalorifer, yerden ısıtma ve fan coil devreleri farklı debi ve basma ister. Yerden ısıtmada devreler uzun olduğu için basma ihtiyacı genellikle radyatörlü sisteme göre daha yüksektir.</li>
<li><strong>Debi:</strong> Kazan veya ısı pompası kapasitesi ile gidiş-dönüş sıcaklık farkına göre hesaplanır. Örneğin 24 kW ısı yükü ve 20 °C sıcaklık farkı için yaklaşık 1 m³/h debi gerekir.</li>
<li><strong>Basma yüksekliği:</strong> Bina yüksekliğine değil, devredeki boru, dirsek, vana ve radyatör dirençlerine bağlıdır. Kapalı devrede suyun ağırlığı gidiş ve dönüşte dengelendiği için kat sayısı tek başına belirleyici değildir.</li>
<li><strong>Bağlantı ve montaj boyu:</strong> Eski pompayı değiştirirken rakor ölçüsü (1" veya 1¼") ve montaj boyu (130 veya 180 mm) aynı olmalıdır. DN40 ve üzeri hatlarda flanşlı modeller seçilir.</li>
<li><strong>Frekans konvertörü:</strong> SSP INV modeller ısı ihtiyacı azaldığında devrini düşürür; sabit hızlı pompalara göre daha az enerji harcar.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/sirkulasyon-pompasi-nedir-nasil-secilir">sirkülasyon pompası nedir, nasıl seçilir</a> rehberimizi okuyabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Sirkülasyon pompası kalorifer sisteminde neden gereklidir?', [
                    ['q' => 'Sirkülasyon pompası ne işe yarar?', 'a' => 'Kapalı devre ısıtma ve soğutma tesisatında suyu ısı kaynağı ile radyatör, yerden ısıtma borusu veya fan coil arasında sürekli dolaştırır. Pompa olmadan sıcak su uzaktaki radyatörlere yeterince ulaşmaz; bazı odalar ısınmaz ve kazan verimsiz çalışır.'],
                    ['q' => 'Sirkülasyon pompası ile hidrofor arasındaki fark nedir?', 'a' => 'Sirkülasyon pompası kapalı devredeki aynı suyu düşük basmayla döndürür. Hidrofor ise açık devrede şebeke veya depo suyunu basınçlandırarak musluklara gönderir. İkisi birbirinin yerine kullanılamaz.'],
                    ['q' => 'Sirkülasyon pompası gidişe mi dönüşe mi takılır?', 'a' => 'Kazan ve tesisat üreticisinin talimatına göre değişir. Birçok bireysel kalorifer tesisatında pompa dönüş hattına, kazan girişine yakın takılır; bu konumda daha düşük sıcaklıkta çalışır. Montajda motor mili yatay kalmalı ve gövdedeki ok akış yönünü göstermelidir.'],
                    ['q' => 'Sirkülasyon pompası ses yapıyorsa ne yapılmalı?', 'a' => 'En sık neden tesisattaki havadır. Radyatör pürjörlerinden hava alınmalı ve tesisat basıncı kontrol edilmelidir. Hava alındığı halde ses sürüyorsa pompa devri ihtiyaçtan yüksek olabilir veya rotor tortu nedeniyle zorlanıyor olabilir. Ayrıntılar için <a href="/blog/sirkulasyon-pompa-calismiyor-ariza-rehberi">sirkülasyon pompası arıza rehberi</a> yazımıza bakabilirsiniz.'],
                    ['q' => 'Frekans konvertörlü (INV) sirkülasyon pompasının farkı nedir?', 'a' => 'Sabit hızlı pompalar her zaman aynı devirde çalışır. Frekans konvertörlü pompalar ise termostatik vanalar kısıldığında veya ısı ihtiyacı azaldığında devrini düşürür; bu da enerji tüketimini ve akış sesini azaltır. Bu kategorideki Sumak SSP rekorlu ve flanşlı modellerin tamamı frekans konvertörlüdür.'],
                ]],
            ],
            150 => [
                'description' => ['<h2>Rekorlu Dişli Sirkülasyon Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Rekorlu Dişli Sirkülasyon Pompası Modelleri ve Fiyatları</h2>
<p><strong>Rekorlu (dişli) sirkülasyon pompası</strong>, boru hattına dişli rakorlarla bağlanan kompakt sirkülasyon pompasıdır. Daire, müstakil ev ve villalardaki kalorifer ve yerden ısıtma devrelerinde suyu kazan ile radyatörler arasında dolaştırır.</p>
<p>Bu kategoride Sumak SSP INV serisinin frekans konvertörlü rekorlu modelleri yer alır:</p>
<ul>
<li><strong>SSP 25/6-130:</strong> 6 mss basma, 4.8 m³/h debi, 1" rakor, 130 mm montaj boyu.</li>
<li><strong>SSP 25/8-180 ve SSP 32/8-180:</strong> 8 mss basma, 5.4–6 m³/h debi, 1¼" rakor, 180 mm montaj boyu.</li>
<li><strong>SSP 25/10-180 ve SSP 32/10-180:</strong> 10 mss basma, 7.2–7.8 m³/h debi, 1¼" rakor, 180 mm montaj boyu.</li>
</ul>
<p>Tüm modeller 220 V monofazedir. DN40 ve üzeri hatlar için <a href="{$cat}/flansli-sirkulasyon-pompalari">flanşlı sirkülasyon pompalarına</a>, tüm seriler için <a href="{$cat}">sirkülasyon pompaları</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Rekorlu Sirkülasyon Pompası Seçimi</h3>
<ul>
<li><strong>Montaj boyu ve rakor:</strong> Mevcut pompayı değiştirirken iki rakor arasındaki mesafe (130 veya 180 mm) ve rakor ölçüsü aynı olmalıdır; böylece boru hattında değişiklik gerekmez.</li>
<li><strong>Basma yüksekliği:</strong> Daire ve küçük evlerde genellikle 6 mss, geniş villalarda ve uzun yerden ısıtma devrelerinde 8–10 mss modeller tercih edilir.</li>
<li><strong>Montaj konumu:</strong> Pompa motor mili yatay olacak şekilde takılmalı, gövdedeki ok akış yönünü göstermelidir. Pompanın iki yanına vana konması, değişim sırasında tesisatın boşaltılmasını önler.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/rekorlu-disli-sirkulasyon-pompa-rehberi">rekorlu sirkülasyon pompa rehberi</a> ve <a href="/blog/yerden-isitma-sirkulasyon-pompasi-secimi">yerden ısıtma için sirkülasyon pompası seçimi</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Rekorlu sirkülasyon pompası hangi boru çaplarına uyuyor?', [
                    ['q' => 'Rekorlu sirkülasyon pompası hangi rakor ölçüleriyle gelir?', 'a' => 'Bu kategorideki Sumak SSP INV modellerinden SSP 25/6-130 1" rakorlu, diğerleri 1¼" rakorludur. Satın almadan önce mevcut rakor ölçüsünü ve iki rakor arasındaki mesafeyi ölçmeniz önerilir.'],
                    ['q' => 'Model adındaki 25/8-180 ne anlama gelir?', 'a' => 'Sumak SSP adlandırmasında eğik çizgiden sonraki sayı metre cinsinden maksimum basma yüksekliğini, tireden sonraki sayı milimetre cinsinden montaj boyunu gösterir. Örneğin SSP 25/8-180, 8 mss basmalı ve 180 mm montaj boylu modeldir.'],
                    ['q' => 'Rekorlu mu flanşlı mı seçilmeli?', 'a' => 'Daire ve müstakil evlerde 1"–1¼" rakorlu pompalar genellikle yeterlidir. DN40 ve üzeri boru çapına sahip apartman, okul ve iş yeri tesisatlarında flanşlı sirkülasyon pompaları kullanılır.'],
                    ['q' => 'Frekans konvertörlü sirkülasyon pompası ne sağlar?', 'a' => 'Pompa, tesisattaki termostatik vanalar kısıldığında veya ısı ihtiyacı azaldığında devrini düşürür. Böylece sabit hızlı pompaya göre daha az elektrik harcar ve radyatör vanalarında akış sesi azalır.'],
                    ['q' => 'Sirkülasyon pompasının havası nasıl alınır?', 'a' => 'Önce radyatör pürjörlerinden hava alınır ve tesisat basıncı kontrol edilir. Pompada hava tahliye vidası varsa pompa kapalıyken yavaşça açılır, su gelince kapatılır. Hava sorunu sürerse tesisatta kaçak veya genleşme tankı arızası olabilir.'],
                ]],
            ],
            152 => [
                'description' => ['<h2>Inline Sirkülasyon Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Inline Sirkülasyon Pompası Modelleri ve Fiyatları</h2>
<p><strong>Inline pompa</strong>, emme ve basma ağızları aynı eksen üzerinde karşılıklı bulunan ve boru hattının arasına doğrudan monte edilen santrifüj pompadır. Ayrı bir kaide veya şase gerektirmediği için kazan dairelerinde az yer kaplar. Büyük bina ve tesislerin merkezi ısıtma, soğutma ve su transferi hatlarında kullanılır.</p>
<p>Bu kategoride Sumak SML 160/65 ve SML 160/80 inline pompaları yer alır:</p>
<ul>
<li><strong>1450 d/d monofaze modeller:</strong> 1.5 ve 2 HP, 220 V; 8–11.8 mss basma ve 28–54 m³/h debi. Düşük devirli oldukları için basma ihtiyacı az olan ısıtma ve soğutma devrelerinde tercih edilir.</li>
<li><strong>2900 d/d trifaze modeller:</strong> 7.5–25 HP, 380 V; 23.5–49 mss basma ve 50–120 m³/h debi. Yüksek debi ve basma gereken büyük tesisler içindir.</li>
</ul>
<p>Daire ve ev tesisatları için <a href="{$cat}/rekorlu-disli-sirkulasyon-pompalari">rekorlu sirkülasyon pompalarına</a>, DN40–DN65 hatlarda 220 V ile çalışan <a href="{$cat}/flansli-sirkulasyon-pompalari">flanşlı sirkülasyon pompalarına</a>, tüm seriler için <a href="{$cat}">sirkülasyon pompaları</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Inline Pompa Seçimi</h3>
<ul>
<li><strong>Debi ve basma:</strong> Tesisatın ısı yükünden debi, boru ve ekipman dirençlerinden basma yüksekliği hesaplanır; bu değerler pompanın performans eğrisiyle karşılaştırılır.</li>
<li><strong>Devir:</strong> 1450 d/d modeller düşük basmalı devreler, 2900 d/d modeller yüksek basma gereken hatlar içindir.</li>
<li><strong>Elektrik:</strong> 1.5 ve 2 HP modeller 220 V monofaze, 7.5 HP ve üzeri modeller 380 V trifazedir.</li>
<li><strong>Yedekleme:</strong> Kesintisiz ısıtma veya soğutma gereken binalarda iki pompa paralel kurulup biri yedek tutulabilir.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/inline-sirkulasyon-pompa-rehberi">inline sirkülasyon pompa rehberi</a> ve <a href="/blog/sirkulasyon-pompa-egrisi-nasil-okunur">pompa eğrisi nasıl okunur</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Inline pompa ile rekorlu pompa hangisini seçmeliyim?', [
                    ['q' => 'Inline pompa nedir?', 'a' => 'Emme ve basma ağızları aynı eksende karşılıklı olan, boru hattının arasına doğrudan takılan santrifüj pompadır. Ayrı kaide gerektirmediği için kazan dairelerinde yer tasarrufu sağlar ve büyük ısıtma, soğutma ve su transferi hatlarında kullanılır.'],
                    ['q' => 'Inline pompa ile rekorlu sirkülasyon pompası arasındaki fark nedir?', 'a' => 'Rekorlu pompalar küçük ve düşük debili konut tesisatları içindir. Inline pompalar ise daha büyük motor gücü ve debiyle büyük bina ve tesislerde kullanılır; bu kategorideki Sumak SML modelleri 28–120 m³/h debi aralığındadır.'],
                    ['q' => '1450 d/d ile 2900 d/d arasındaki fark nedir?', 'a' => 'Devir yükseldikçe aynı çark daha yüksek basma üretir. 1450 d/d modeller düşük basmalı ısıtma ve soğutma devreleri, 2900 d/d modeller yüksek basma gereken hatlar içindir.'],
                    ['q' => 'Inline pompa nasıl monte edilir?', 'a' => 'Pompa boru hattının arasına flanşlarla bağlanır. Montaj konumu için üretici kılavuzuna uyulmalı, büyük motorlu modellerde pompanın ağırlığı boruya yük bindirmemesi için ayrıca desteklenmelidir. Bakım için pompanın iki yanına vana konması önerilir.'],
                    ['q' => 'Inline pompa ses yapıyorsa sebep ne olabilir?', 'a' => 'Tesisatta hava, pompa girişindeki basıncın düşük olması (kavitasyon), rulman aşınması veya ihtiyaçtan büyük seçilmiş pompa ses yapabilir. İlk olarak hava tahliyesi ve tesisat basıncı kontrol edilmelidir.'],
                ]],
            ],
            153 => [
                'meta_description' => ['Merkezi ısıtma ve soğutma hatları için flanşlı', 'Apartman ve iş yeri kalorifer devreleri için Sumak SSP INV frekans konvertörlü flanşlı sirkülasyon pompaları. DN40, DN50 ve DN65 bağlantı, 220 V.'],
                'description' => ['<h2>Flanşlı Sirkülasyon Pompası Modelleri</h2>', <<<HTML
<h2>Flanşlı Sirkülasyon Pompası Modelleri</h2>
<p><strong>Flanşlı sirkülasyon pompası</strong>, boru hattına dişli rakor yerine flanşla bağlanan ve rekorlu modellere göre daha yüksek debi sunan sirkülasyon pompasıdır. Apartman, okul, otel ve iş yerlerinin merkezi kalorifer ve yerden ısıtma devrelerinde, kazan ile tesisat arasında suyu dolaştırır.</p>
<p>Bu kategoride Sumak SSP INV serisinin frekans konvertörlü flanşlı modelleri yer alır:</p>
<ul>
<li><strong>SSP 40:</strong> DN40 flanşlı; 12–15 mss basma ve 20–26 m³/h debi.</li>
<li><strong>SSP 50:</strong> DN50 flanşlı; 11–15 mss basma ve 26–30 m³/h debi.</li>
<li><strong>SSP 65:</strong> DN65 flanşlı; 9–12 mss basma ve 42–46 m³/h debi.</li>
</ul>
<p>Tüm modeller 220 V monofaze beslemeyle çalışır. Daire ve ev tesisatları için <a href="{$cat}/rekorlu-disli-sirkulasyon-pompalari">rekorlu sirkülasyon pompalarına</a>, daha yüksek debi ve basma için <a href="{$cat}/inline-sirkulasyon-pompalari">inline pompalara</a>, tüm seriler için <a href="{$cat}">sirkülasyon pompaları</a> kategorisine bakabilirsiniz.</p>
HTML],
                'buying_guide' => ['', <<<'HTML'
<h3>Flanşlı Sirkülasyon Pompası Seçimi</h3>
<ul>
<li><strong>Flanş çapı:</strong> Pompa flanşı tesisattaki boru ve vana flanşıyla aynı çapta (DN40, DN50 veya DN65) olmalıdır.</li>
<li><strong>Flanşlar arası mesafe:</strong> Eski pompayı değiştirirken iki flanş arasındaki montaj boyunu ölçün; yeni pompanın boyu farklıysa ara parça gerekir.</li>
<li><strong>Debi ve basma:</strong> Kazan kapasitesi ve gidiş-dönüş sıcaklık farkından debi, tesisat dirençlerinden basma yüksekliği hesaplanır.</li>
<li><strong>Elektrik:</strong> SSP INV flanşlı modeller 220 V ile çalıştığı için trifaze hat gerektirmez.</li>
</ul>
<p>Ayrıntılar için <a href="/blog/flansli-sirkulasyon-pompa-rehberi">flanşlı sirkülasyon pompa rehberi</a> ve <a href="/blog/frekans-kontrollu-sirkulasyon-pompa-avantajlari">frekans kontrollü sirkülasyon pompasının avantajları</a> yazılarımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Flanşlı sirkülasyon pompası hangi sistemlerde kullanılır?', [
                    ['q' => 'Flanşlı sirkülasyon pompası hangi sistemlerde kullanılır?', 'a' => 'Apartman, okul, otel ve iş yerlerindeki merkezi kalorifer, yerden ısıtma ve kazan devrelerinde kullanılır. Genellikle DN40 ve üzeri boru çaplarında rekorlu pompa yerine flanşlı model seçilir.'],
                    ['q' => 'Flanşlı sirkülasyon pompası mı, inline pompa mı?', 'a' => 'Bu kategorideki Sumak SSP INV flanşlı modeller 220 V ile çalışan, 46 m³/h\'e kadar debili frekans konvertörlü sirkülasyon pompalarıdır. Daha yüksek debi ve basma gereken büyük tesislerde 120 m³/h\'e kadar debili Sumak SML inline pompalar kullanılır.'],
                    ['q' => 'Sirkülasyon pompası frekans konvertörlü olmalı mı?', 'a' => 'Termostatik vanalı veya bölgesel kontrollü tesisatlarda debi gün içinde değişir. Frekans konvertörlü pompa bu değişime göre devrini düşürür; gereksiz enerji tüketimini ve vanalardaki akış sesini azaltır.'],
                    ['q' => 'Flanştaki PN değeri ne anlama gelir?', 'a' => 'PN, flanşın nominal basınç sınıfını gösterir; PN10 10 bar, PN16 16 bar içindir. Pompa ve tesisat flanşlarının aynı standartta olması gerekir. Pompanın flanş sınıfını ürün etiketinden veya kullanım kılavuzundan kontrol edin.'],
                    ['q' => 'Flanşlı pompa ses yapıyorsa sebep ne olabilir?', 'a' => 'Tesisatta hava, düşük tesisat basıncı, tortu veya ihtiyaçtan yüksek pompa devri ses yapabilir. İlk olarak hava tahliyesi ve tesisat basıncı kontrol edilmelidir.'],
                ]],
            ],
            156 => [
                'meta_description' => ['Boyler, güneş enerjisi ve merkezi sıcak su hatları', 'Kazan dairesi, boyler ve güneş enerjisi hatları için 90 °C\'ye dayanıklı Sumak sıcak su pompaları: preferikal, santrifüj, çift kademeli ve paslanmaz modeller.'],
                'description' => ['<h2>Sıcak Su Pompası Modelleri ve Fiyatları</h2>', <<<HTML
<h2>Sıcak Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Sıcak su pompası</strong>, 90 °C'ye kadar sıcak suyu basmak ve transfer etmek için contası ve gövde malzemesi sıcaklığa uygun seçilmiş pompadır. Kazan daireleri, boyler ve eşanjör hatları, güneş enerjisi tesisatları ve proses sıcak suyu gibi uygulamalarda kullanılır. Standart soğuk su pompalarının salmastrası bu sıcaklıklarda kısa sürede yıpranabilir.</p>
<p>Bu kategoride Sumak'ın 90 °C'ye kadar çalışan -S serisi pompaları yer alır:</p>
<ul>
<li><strong>Preferikal pompalar (SM ve SMT 5-S, 10-S):</strong> 0.5–1 HP; düşük debide 75 mSS'ye kadar basma.</li>
<li><strong>Santrifüj pompalar (SM ve SMT 100-S ile 1500-S arası):</strong> 1–15 HP; 125 m³/h'e kadar debiyle sıcak su transferi.</li>
<li><strong>Çift kademeli pompalar (SMK ve SMKT):</strong> 2.2–7.5 HP; 100 mSS'ye kadar basma gereken sıcak su hatları.</li>
<li><strong>Paslanmaz pompalar (SMINOX/A ve SMINOX/K):</strong> Açık veya kapalı fanlı, 1–1.5 HP paslanmaz santrifüj modeller.</li>
</ul>
<p>SM, SMK ve SMINOX modelleri 220 V monofaze, SMT ve SMKT modelleri 380 V trifazedir.</p>
<h3>Sıcak Su Pompası mı, Sirkülasyon Pompası mı?</h3>
<p>Kalorifer ve yerden ısıtma devresinde suyu kazan ile radyatörler arasında dolaştırmak için <a href="{$cat}/rekorlu-disli-sirkulasyon-pompalari">rekorlu</a> veya <a href="{$cat}/flansli-sirkulasyon-pompalari">flanşlı sirkülasyon pompası</a> kullanılır. Bu kategorideki pompalar ise sıcak suyu bir noktadan diğerine basmak veya basınçlandırmak içindir. Güneş enerjili evlerde musluk basıncı gerekiyorsa <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforu</a> daha uygundur.</p>
HTML],
                'buying_guide' => ['<h3>Sıcak Su Sirkülasyon Pompası Nasıl Seçilir?</h3>', <<<'HTML'
<h3>Sıcak Su Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Sıcaklık:</strong> Suyun en yüksek sıcaklığı 90 °C'yi geçmemelidir; daha sıcak hatlar için bu kategorideki pompalar uygun değildir.</li>
<li><strong>Debi ve basma:</strong> Düşük debi ve yüksek basma için preferikal veya çift kademeli, yüksek debi için santrifüj modeller seçilir.</li>
<li><strong>Besleme koşulu:</strong> Sıcak su kolay buharlaştığı için pompa suyu emerek çekmemeli, depo veya kazan pompadan yüksekte kalacak şekilde beslenmelidir. Aksi halde kavitasyon oluşur ve pompa ses yapar.</li>
<li><strong>Elektrik:</strong> Yalnızca 220 V varsa SM, SMK ve SMINOX; trifaze hat varsa SMT ve SMKT modelleri seçilir.</li>
</ul>
<p>Sıcak su devridaim uygulamaları için <a href="/blog/sicak-su-sirkulasyon-pompasi-secimi">sıcak su sirkülasyon pompası seçimi</a> yazımıza bakabilirsiniz.</p>
HTML.self::CONTACT],
                'faq' => ['Sıcak su pompası kaç dereceye dayanır?', [
                    ['q' => 'Sıcak su pompası kaç dereceye dayanır?', 'a' => 'Bu kategorideki Sumak -S serisi pompalar 90 °C\'ye kadar sıcak su için üretilmiştir. Sıcaklığı bu değeri aşan hatlarda kullanılmamalıdır.'],
                    ['q' => 'Soğuk su pompası sıcak suda kullanılabilir mi?', 'a' => 'Önerilmez. Standart pompaların mekanik salmastrası ve contaları yüksek sıcaklıkta çabuk yıpranır ve pompa sızdırmaya başlar. Sıcak su hattında sıcaklığa uygun -S serisi gibi modeller kullanılmalıdır.'],
                    ['q' => 'Sıcak su sirkülasyon pompası ile sıcak su pompası aynı mı?', 'a' => 'Günlük dilde ikisi de kullanılır ama işlevleri farklıdır. Sirkülasyon pompası kapalı ısıtma devresinde suyu düşük basmayla sürekli dolaştırır; sıcak su pompası ise sıcak suyu bir yerden başka bir yere basar veya basınçlandırır. Kalorifer devresi için rekorlu ya da flanşlı sirkülasyon pompası, sıcak su transferi için bu kategorideki pompalar seçilir.'],
                    ['q' => 'Sıcak su pompası neden ses yapar?', 'a' => 'Sıcak suda en sık neden kavitasyondur: pompa girişindeki basınç düşük olduğunda su buharlaşır ve çarkta vuruntu sesi oluşur. Pompanın depo veya kazan seviyesinin altına kurulması, emiş hattının kısa ve geniş tutulması bu riski azaltır.'],
                    ['q' => 'Sıcak su pompası ile sıcak su hidroforu arasındaki fark nedir?', 'a' => 'Sıcak su pompası tek başına suyu basar. Sıcak su hidroforu ise pompa, basınç tankı ve basınç şalterinden oluşan hazır sistemdir; musluk açılınca devreye girip kapanınca durarak tesisatta sabit basınç sağlar. Güneş enerjili evlerde musluk basıncı için <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforu</a> tercih edilir.'],
                ]],
            ],
        ];
    }
};
