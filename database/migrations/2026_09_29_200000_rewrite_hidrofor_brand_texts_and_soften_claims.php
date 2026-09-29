<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * [kategori slug'ı, eski description md5, yeni description, eski faq md5, yeni faq].
     * Panelden değiştirilmiş alanın üzerine yazılmaz.
     */
    private function rewrites(): array
    {
        return [
            [
                'pedrollo-hidrofor',
                '51bcd8279f66dd8a0ddcf74d7ce0e582',
                <<<'HTML'
<h2>Pedrollo Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Pedrollo hidrofor</strong> paketleri, İtalyan Pedrollo pompalarının hidromat veya basınç tankıyla birleştirildiği hazır basınçlandırma setleridir. Bu kategoride 2 kat ve 2 daireden 9 kat ve 20 daireye kadar binalar için tek pompalı paket hidroforlar bulunur. Ürün adındaki kat ve daire değeri, model seçiminde ilk ölçüdür.</p>
<h3>Pedrollo Hidrofor Serileri</h3>
<ul>
  <li><strong>PKm 60</strong> — Preferikal (sürtme fanlı) pompalı mini hidrofor; 2 kat ve 2 daire, küçük konut ile bağ-bahçe kullanımı için.</li>
  <li><strong>JSWm 2CX ve 2AX</strong> — Döküm gövdeli, kendinden emişli jet pompalı paket hidrofor; 4 kat 6 daire ve 6 kat 10 daire seçenekleri.</li>
  <li><strong>JCRm 1A ve 2A</strong> — Paslanmaz gövdeli, kendinden emişli jet pompalı hidrofor; 2 kat 4 daireden 6 kat 10 daireye kadar.</li>
  <li><strong>4CPm 80-C ve 100-C</strong> — Çok çarklı, sessiz çalışan paket hidrofor; 4 kat 8 daire ve 5 kat 12 daire.</li>
  <li><strong>2CPm 25/130N, 25/14B ve 25/14A</strong> — Çift çarklı santrifüj pompalı, dijital kontrollü ve 50 litre tanklı paket hidrofor; 4 kat 10 daireden 9 kat 20 daireye kadar.</li>
</ul>
<h3>Hidromatlı, Tanklı ve Dijital Modeller</h3>
<p>Aynı pompa farklı kontrol tipleriyle sunulur. <strong>Hidromatlı</strong> modellerde basınç tankı yoktur; pompa musluk açılınca çalışır, kapanınca durur ve az yer kaplar. <strong>24 veya 50 litre tanklı</strong> modellerde tank küçük su çekimlerini karşılar, bu sayede pompa daha seyrek devreye girer. <strong>Dijital</strong> modellerde elektronik kontrol ünitesi bulunur; özellikleri ürün sayfasında yer alır. Eşzamanlı kullanımın yoğun olduğu evlerde tanklı model, dar alanlarda hidromatlı model öne çıkar.</p>
<p><a href="/kategoriler/hidrofor-sistemleri">Tüm hidrofor sistemleri</a> | <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak hidrofor modelleri</a> | <a href="/marka/pedrollo">Pedrollo marka sayfası</a></p>
HTML,
                '3c1c3def7b3dbc0a948efe57577ea2cc',
                [
                    [
                        'q' => 'Pedrollo hidrofor kaç katlı binaya yeter?',
                        'a' => 'Ürün adındaki kat ve daire değeri seçimde ilk ölçüdür. Bu kategoride <strong>PKm 60</strong> 2 kat ve 2 daire, <strong>JSWm 2CX</strong> 4 kat ve 6 daire, <strong>JSWm 2AX</strong> 6 kat ve 10 daire, <strong>JCRm 2A</strong> 5-6 kat ve 10 daire, <strong>4CPm 100-C</strong> 5 kat ve 12 daire, <strong>2CPm 25/14A</strong> ise 9 kat ve 20 daire için önerilir. Bina daha büyükse veya eşzamanlı kullanım çok yüksekse <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a> değerlendirilmelidir.',
                    ],
                    [
                        'q' => 'Hidromatlı mı, tanklı Pedrollo hidrofor mu seçmeliyim?',
                        'a' => 'Hidromatlı modeller tanksızdır; az yer kaplar ve pompayı akışa göre çalıştırıp durdurur, ancak küçük su çekimlerinde de pompa devreye girer. <strong>24 veya 50 litre tanklı</strong> modellerde tank bu küçük çekimleri karşılar; pompa daha seyrek çalışır ve basınç dalgalanması azalır. Birden fazla banyo ve mutfak aynı anda kullanılıyorsa tanklı model, dar bir alana kurulum gerekiyorsa hidromatlı model daha uygundur.',
                    ],
                    [
                        'q' => 'JSWm ile JCRm arasındaki fark nedir?',
                        'a' => 'İkisi de kendinden emişli jet pompalı paket hidrofordur; pompa seviyesinin altındaki depodan veya sığ kuyudan su emebilir. <strong>JSWm</strong> döküm gövdelidir. <strong>JCRm</strong> paslanmaz gövdelidir ve korozyona daha dayanıklıdır; benzer kapasitede fiyatı biraz daha yüksektir. Kat ve daire değerleri iki seride yakındır; seçim bütçeye ve su koşullarına göre yapılır.',
                    ],
                    [
                        'q' => '4CPm ve 2CPm hangi binalar için uygundur?',
                        'a' => '<strong>4CPm</strong> çok çarklı ve sessiz çalışan bir pompadır; 4-5 katlı, 8-12 daireli binalarda gürültünün önemli olduğu kurulumlar için uygundur. <strong>2CPm</strong> çift çarklı santrifüj pompalı, dijital kontrollü ve 50 litre tanklı paketlerdir; 4 kat 10 daireden 9 kat 20 daireye kadar daha yüksek binalar için sunulur. Depo pompa seviyesinin altındaysa emme hattı ve dip klapesi doğru kurulmalıdır.',
                    ],
                    [
                        'q' => 'Pedrollo hidrofor tank ön basıncı ne olmalı?',
                        'a' => 'Tanklı modellerde tankın hava tarafı basıncı, pompanın devreye girme basıncının yaklaşık <strong>0,2-0,3 bar altında</strong> olmalıdır. Ölçüm, pompa kapalıyken ve tesisattaki su boşaltılmışken tank üzerindeki valften manometreyle yapılır. Ön basınç düşükse pompa sık devreye girer; bu kontrolün yılda bir yapılması önerilir.',
                    ],
                ],
            ],
            [
                'sumak-hidrofor',
                '44b9a0396c617fcb421d7f258c2e5f59',
                <<<'HTML'
<h2>Sumak Hidrofor Modelleri ve Fiyatları</h2>
<p><strong>Sumak hidrofor</strong> modelleri, Türkiye'de üretilen Sumak pompalarının hidromat veya basınç tankıyla hazır set hâlinde sunulduğu basınçlandırma sistemleridir. Bu kategoride 2 kat ve 2 daireden 14 kata veya 34 daireye kadar ev tipi paket hidroforlar ile yatay kademeli bina hidroforları yer alır.</p>
<h3>Sumak Hidrofor Serileri</h3>
<ul>
  <li><strong>SM5</strong> — Küçük ev tipi hidrofor; 2 kat ve 2 daire, hidromatlı veya 24 litre tanklı.</li>
  <li><strong>SM 10 ve SM 15</strong> — Ev ve bahçe hidroforu; 4 kat, 4-6 daire.</li>
  <li><strong>SMJ 85, 100, 150 ve 220</strong> — Kendinden emişli jet pompalı paket hidrofor; 4 kat 4 daireden 8 kat 16 daireye kadar, hidromatlı, 24 veya 50 litre tanklı.</li>
  <li><strong>SMinoxH 150/4</strong> — Paslanmaz pompalı paket hidrofor.</li>
  <li><strong>SMH 120 BOX</strong> — Kabinli, frekans kontrollü ve sessiz çalışan hidrofor (1,2 HP).</li>
  <li><strong>SYMH ve SYMTH</strong> — 100 litre tanklı yatay kademeli bina hidroforu; 6 kattan 14 kata, 13 daireden 34 daireye kadar, 1-3 HP, 220 V ve 380 V seçenekleri.</li>
</ul>
<h3>Model Seçimi</h3>
<p>Ürün adındaki kat ve daire değeri ilk seçim ölçüsüdür. Müstakil ev ve küçük apartmanlarda SM veya SMJ paket hidrofor, 6 kat ve üzeri ya da çok daireli binalarda SYMH bina hidroforu değerlendirilir. Hidromatlı modeller az yer kaplar; tanklı modellerde pompa daha seyrek devreye girer. Daha yüksek binalar ve yedekli çalışma ihtiyacı için <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a> gerekir.</p>
<p><a href="/kategoriler/hidrofor-sistemleri">Tüm hidrofor sistemleri</a> | <a href="/kategoriler/hidrofor-sistemleri/pedrollo-hidrofor">Pedrollo hidrofor modelleri</a> | <a href="/marka/sumak">Sumak marka sayfası</a></p>
HTML,
                'd6b862b9b82e92661d2f11d480eb890c',
                [
                    [
                        'q' => 'Sumak hidrofor kaç katlı binaya yeter?',
                        'a' => 'Ürün adındaki kat ve daire değeri seçimde ilk ölçüdür. <strong>SM5</strong> 2 kat ve 2 daire, <strong>SMJ 85</strong> ve <strong>SM 10</strong> 4 kat ve 4 daire, <strong>SMJ 150</strong> 5 kat ve 10 daire, <strong>SMJ 220</strong> 8 kat ve 16 daire için önerilir. Daha büyük binalarda 100 litre tanklı <strong>SYMH</strong> bina hidroforları 14 kata veya 34 daireye kadar seçenek sunar.',
                    ],
                    [
                        'q' => 'SYMH bina hidroforu ne zaman tercih edilir?',
                        'a' => 'SYMH, yatay kademeli pompa ve 100 litre tankla çalışan bina hidroforudur. Paket hidroforların yetmediği 6 kat ve üzeri veya çok daireli binalar için sunulur; modele göre 6 kat 13 daireden 14 kat 20 daireye ya da 6 kat 34 daireye kadar seçenekleri vardır. Seçimde 220 V veya 380 V besleme de dikkate alınmalıdır. Yedek pompa gerekiyorsa <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a> değerlendirilmelidir.',
                    ],
                    [
                        'q' => 'Tanklı Sumak hidroforda basınç ayarı nasıl yapılır?',
                        'a' => 'Ayardan önce elektriği kesin. Presostat kapağı açıldığında içinde iki yay görülür: büyük yay <strong>devreye girme ve durma basıncını birlikte</strong>, küçük yay ise ikisi arasındaki farkı (diferansiyel) ayarlar. Büyük yayı saat yönünde çevirmek iki basıncı da yükseltir. Tipik ayar: devreye girme 1,5 bar, durma 2,5-3 bar. Ayardan sonra pompayı çalıştırıp manometre değerini gözlemleyin.',
                    ],
                    [
                        'q' => 'Sumak hidrofor neden aşırı sık açılıp kapanıyor?',
                        'a' => 'Pompanın çok sık devreye girip durması genellikle <strong>basınç tankı membranının yırtılmasından</strong> veya tank ön basıncının düşmesinden kaynaklanır. Membran yırtılırsa tankın hava yastığı işlevi ortadan kalkar ve pompa her küçük su talebiyle devreye girer. Test için sistemi durdurun ve tank üzerindeki hava valfine kısa bir süre basın; hava yerine su geliyorsa membran yırtılmış demektir. Bu durumda membran veya tank değiştirilmelidir.',
                    ],
                    [
                        'q' => 'Sumak hidrofor tankının ön basıncı nasıl kontrol edilir?',
                        'a' => 'Sistemi kapatın ve su basıncını sıfırlayın (tüm muslukları açın). Basınç tankı üzerindeki <strong>hava valfini</strong> lastik basınç ölçeriyle ölçün. Ön basınç genellikle pompanın devreye girme basıncının <strong>yaklaşık %90\'ı</strong> olmalıdır; örneğin devreye girme 1,5 bar ise ön basınç 1,3-1,4 bar civarındadır. Gerekirse hava pompasıyla hava ekleyin veya valften hava boşaltın.',
                    ],
                ],
            ],
        ];
    }

    /**
     * [kategori slug'ı, alan (description|faq), eski metin, yeni metin].
     * Eski metin bulunamazsa (panelden değişmişse) atlanır.
     */
    private function replacements(): array
    {
        return [
            ['hidrofor-sistemleri', 'faq',
                'Çok pompalı ve frekans invertörlü büyük hidrofor grupları ise profesyonel tesisatçı veya yetkili servis tarafından <strong>1-2 günde</strong> devreye alınır. Koşar olarak İstanbul ve çevresi için yetkili servis yönlendirmesi yapabiliyoruz.',
                'Çok pompalı ve frekans kontrollü büyük hidrofor grupları ise profesyonel tesisatçı tarafından, projenin kapsamına göre genellikle <strong>1-2 günde</strong> devreye alınır. Model ve kapasite seçimi için ekibimizden teknik destek alabilirsiniz.'],
            ['hidroforlar', 'faq',
                '<strong>Sumak</strong> ise Türkiye\'de üretilen, yaygın servis ağı ve uygun fiyatıyla konut segmentinde çok tercih edilen güvenilir bir markadır. Her iki markanın da garantili yetkili servis ağına sahibiz; ihtiyacınıza ve bütçenize göre doğru modeli belirlemenize yardımcı olabiliriz.',
                '<strong>Sumak</strong> ise Türkiye\'de üretilen, uygun fiyatıyla konut projelerinde sık tercih edilen bir markadır. Koşar Ticaret her iki markanın da yetkili bayisidir; ihtiyacınıza ve bütçenize göre doğru modeli belirlemenize yardımcı olabiliriz.'],
            ['hidrofor-grubu', 'faq',
                '<strong>kullanım miktarına göre pompa hızını otomatik ayarlayarak enerji tüketimini %30-50 azaltır</strong>',
                '<strong>kullanım miktarına göre pompa hızını otomatik ayarlayarak enerji tüketimini azaltır</strong>'],
            ['hidrofor-grubu', 'faq',
                'Yatırım maliyeti 2-4 yıl içinde enerji tasarrufu ile geri dönebilir.',
                'Tasarrufun büyüklüğü binanın kullanım profiline ve çalışma saatine bağlıdır.'],
            ['hidrofor-grubu', 'faq',
                'Koşar olarak yetkili tesisat ekipleriyle proje bazlı kurulum desteği sağlıyoruz; sisteme özel teknik hesaplama ve devreye alma belgeleri de sunulur.',
                'Kurulum ve devreye almayı projeyi yürüten tesisat firması yapmalıdır; model ve kapasite seçimi için ekibimizden teknik destek alabilirsiniz.'],
            ['hidrofor-grubu', 'faq',
                'Düzenli bakım arıza riskini minimize eder ve pompanın 15+ yıl çalışmasını sağlar.',
                'Düzenli bakım arıza riskini azaltır ve pompa ömrünü uzatır.'],
            ['sicak-su-hidroforu', 'faq',
                'Merkezi tesislerde yetkili servis bakımı zorunludur.',
                'Merkezi tesislerde bakımın yetkin bir servis tarafından yapılması önerilir.'],
            ['su-pompalari', 'faq',
                'Koşar\'da sattığımız tüm pompalar <strong>2 yıl üretici garantisi</strong> kapsamındadır. Pedrollo, Sumak ve Ebara markalı ürünler yetkili servis ağıyla desteklenir.',
                'Garanti süresi ve kapsamı marka ile modele göre değişir; güncel bilgi ürün sayfasında ve garanti belgesinde yer alır.'],
            ['temiz-su-dalgic-pompasi', 'description',
                'Pompanın gövde, impeller ve bağlantı parçaları <strong>NSF/WRAS belgeli gıda uyumlu plastik veya 304 paslanmaz çelik</strong> malzemeden üretilir; bu sayede suyun kimyasal bileşimini değiştirmez.',
                'İçme ve kullanma suyunda çalışacak modellerde gövde, çark ve bağlantı parçalarının <strong>suya uygun plastik veya paslanmaz çelik</strong> malzemeden olması gerekir; malzeme bilgisi ürün sayfasında yer alır.'],
            ['temiz-su-dalgic-pompasi', 'faq',
                'Temiz su dalgıç pompası, <strong>gıda uyumlu (NSF/WRAS belgeli) malzemelerden</strong> üretilir ve içme suyu kalitesini korur; sadece temiz suda çalıştırılabilir',
                'Temiz su dalgıç pompası, <strong>temiz suya uygun malzemelerle</strong> üretilir ve yalnızca temiz suda çalıştırılmalıdır'],
            ['temiz-su-dalgic-pompasi', 'faq',
                'tasarlanmış olup gıda belgesi yoktur.',
                'tasarlanmıştır.'],
            ['temiz-su-dalgic-pompasi', 'faq',
                '; IP68 koruma sınıfı bunu garanti eder.',
                '; motorun IP68 koruma sınıfında olması bunun ön koşuludur.'],
            ['tek-fanli-santrifuj-pompa', 'description',
                'Tüm santrifüj pompaların yaklaşık %70\'ini oluşturan bu tip; tarımsal sulama',
                'Bu tip; tarımsal sulama'],
            ['rekorlu-disli-sirkulasyon-pompalari', 'description',
                ' Türkiye\'deki konut ve küçük ticari ısıtma tesisatlarının <strong>%80\'inden fazlasında</strong> bu pompa tipi kullanılır.',
                ''],
            ['rekorlu-disli-sirkulasyon-pompalari', 'faq',
                'Günümüzün A+++ sınıfı ECM motorlu pompaları <strong>5-25 W</strong> tüketimiyle aynı performansı sağlar; enerji faturasında yılda 200-400 TL tasarruf edebilir ve 2-3 yılda kendini amorti eder.',
                'Frekans kontrollü (değişken hızlı) pompalar ısı ihtiyacı azaldığında hızını düşürerek daha az enerji çeker; tasarrufun büyüklüğü çalışma süresine ve tesisata bağlıdır.'],
            ['kademeli-pompalar', 'faq',
                'Bu, enerji tüketiminde %30-50\'ye varan tasarruf, pompa ömrünün uzaması ve sabit çıkış basıncı anlamına gelir.',
                'Bu, değişken kullanımda daha düşük enerji tüketimi, pompa ömrünün uzaması ve sabit çıkış basıncı anlamına gelir.'],
            ['kademeli-pompalar', 'faq',
                'Özellikle yük değişkenliğinin fazla olduğu binalar ve proses hatları için yatırım maliyeti hızla geri döner.',
                'Kazanç özellikle yük değişkenliğinin fazla olduğu binalarda ve proses hatlarında belirgindir.'],
            ['dikey-kademeli-pompalar', 'faq',
                'Sabit hızlı pompalarda başarılabilir enerji tasarrufu inverter ile %30-50\'ye ulaşır. Özellikle değişken kullanım profili olan konutlarda (gündüz az, sabah-akşam yoğun) inverter entegrasyonu yatırım maliyetini 2-4 yılda geri öder.',
                'Sabit hızlı pompaya göre enerji tüketimi, özellikle değişken kullanım profili olan konutlarda (gündüz az, sabah-akşam yoğun) belirgin şekilde düşer; geri dönüş süresi çalışma saatine bağlıdır.'],
            ['santrifuj-pompalar', 'description',
                'IE3 sınıfı yüksek verimli motorlarla üretilen modern santrifüj pompalar, aynı debide eski nesil pompalara kıyasla <strong>%15-25 daha az enerji</strong> tüketir. Hız kontrollü (inverter) sistemlerle birlikte kullanıldığında bu tasarruf %40\'a kadar çıkabilir.',
                'Yüksek verimli motorlu ve doğru çalışma noktasına göre seçilmiş bir santrifüj pompa, aynı debide <strong>daha az enerji</strong> tüketir. Değişken debili sistemlerde hız kontrolü (inverter) tasarrufu artırır; kazanç çalışma saatine ve sistem eğrisine bağlıdır.'],
            ['santrifuj-pompalar-sulama', 'faq',
                'inverter kullanımı <strong>%20-40 enerji tasarrufu</strong> sağlar.',
                'inverter kullanımı <strong>enerji tüketimini düşürür</strong>.'],
            ['jet-pompalar-derinden-emisli', 'description',
                'Jet pompalar dalgıç pompalara göre %20-30 daha az verimlidir çünkü',
                'Jet pompalar dalgıç pompalara göre daha düşük verimle çalışır çünkü'],
            ['sirkulasyon-pompalari', 'description',
                'Yüksek verimli ECM motorlu sirkülasyon pompaları <strong>%80\'e varan verim</strong> oranıyla enerji tasarrufu sağlar.',
                'Frekans konvertörlü (değişken hızlı) sirkülasyon pompaları, ısı ihtiyacı azaldığında hızını düşürerek <strong>enerji tüketimini azaltır</strong>.'],
            ['paslanmaz-drenaj-dalgic-pompa', 'description',
                'paslanmaz modeller <strong>5-10 kat daha uzun ömür</strong> sunar.',
                'paslanmaz modeller <strong>çok daha uzun ömürlü</strong> olur.'],
            ['paslanmaz-drenaj-dalgic-pompa', 'faq',
                'belgesi olması zorunludur. Bu belgeler pompa malzemelerinin gıda temaslı yüzeylerde güvenli olduğunu kanıtlar.',
                'belgesi aranmalıdır; belge durumu model bazında üreticiden teyit edilmelidir.'],
            ['paslanmaz-drenaj-dalgic-pompa', 'faq',
                'Contalar da gıda uyumlu EPDM veya PTFE malzemeden olmalıdır; standart NBR kauçuk conta içme suyu sistemlerinde kullanılamaz.',
                'Contaların da gıda uyumlu EPDM veya PTFE gibi uygulamaya uygun malzemeden olması gerekir.'],
            ['ozel-amacli-pompalar', 'description',
                '<li><strong>Yangın Pompaları</strong> — EN 12845 standardına uygun, yüksek güvenilirlikte ve yedekli sistemlerde kullanılmak üzere tasarlanmış.</li>',
                '<li><strong>Yangın Pompaları</strong> — Yangın projesine göre seçilen elektrikli, dizel ve joker pompalı yangın grupları.</li>'],
            ['yangin-pompalari', 'description',
                'Türkiye\'de binaların yangın söndürme sistemleri <strong>TS EN 12845 — Sabit Yangın Söndürme Sistemleri standardı</strong> kapsamında tasarlanmak zorundadır.',
                'Türkiye\'de sprinkler sistemleri <strong>Binaların Yangından Korunması Hakkında Yönetmelik</strong> ve <strong>TS EN 12845</strong> standardı esas alınarak projelendirilir.'],
            ['yangin-pompalari', 'description',
                'Elektrik kesintisinde otomatik devreye girer; zorunlu</li>',
                'Elektrik kesintisinde otomatik devreye girer; gerekliliği projeye göre belirlenir</li>'],
            ['yangin-pompalari', 'description',
                'UL/FM sertifikalı otomatik start, arıza alarm sistemi</li>',
                'Otomatik start, arıza alarmı ve test fonksiyonları</li>'],
            ['yangin-pompalari', 'faq',
                'Evet. Türk Yapı Yönetmeliğine (TBDY) ve <strong>Binaların Yangından Korunması Hakkında Yönetmelik</strong>\'e göre yüksek binalarda (30 m üzeri), kapalı otoparklar, hastaneler, okullar, AVM ve büyük endüstriyel tesislerde sabit yangın söndürme sistemi ve dolayısıyla yangın pompası zorunludur.',
                '<strong>Binaların Yangından Korunması Hakkında Yönetmelik</strong>; yüksek binalar, kapalı otoparklar, hastaneler, AVM\'ler ve büyük endüstriyel tesisler gibi belirli yapılarda sabit yangın söndürme sistemi ister. Bu sistemler yangın pompasıyla beslenir.'],
            ['yangin-pompalari', 'faq',
                'Yangın pompasının elektrikli ve dizel olarak birlikte kullanılması neden zorunlu?',
                'Yangın pompasında elektrikli ve dizel pompa neden birlikte kullanılır?'],
            ['yangin-pompalari', 'faq',
                'Bu nedenle <strong>EN 12845 ve NFPA 20</strong> standartları, yedek enerji kaynağına sahip dizel pompa kullanımını zorunlu kılmaktadır. Dizel pompa şebeke bağımsız olarak çalışır, otomatik start özelliğine sahiptir ve kesintisiz yakıt tankı ile 6-8 saatlik sürekli çalışma sağlar.',
                'Bu nedenle <strong>EN 12845 ve NFPA 20</strong> gibi standartlar, projenin risk sınıfına göre şebekeden bağımsız bir yedek pompa (çoğunlukla dizel) ister. Dizel pompa otomatik start özelliğiyle şebekeden bağımsız çalışır; yakıt tankı kapasitesi projede belirlenir.'],
            ['yangin-pompalari', 'faq',
                'EN 12845 standardı; yangın pompasının <strong>haftada bir test çalıştırması</strong> (en az 10 dakika yüksüz), <strong>üç ayda bir</strong> yük altında test ve <strong>yılda bir kapsamlı test ve bakım</strong> yapılmasını öngörür.',
                'EN 12845, yangın pompaları için <strong>haftalık çalıştırma testi</strong> ile <strong>periyodik kontrol ve yıllık bakım</strong> öngörür; test süresi ve kapsamı proje ile bakım firmasına göre planlanır.'],
            ['yangin-pompalari', 'faq',
                'Yangın pompası için ne kadar su deposu gerekmez?',
                'Yangın pompası için ne kadar su deposu gerekir?'],
            ['yangin-pompalari', 'faq',
                ' EN 12845\'e göre Hazard Sınıfı I binalar için tipik değer 70-182 m³, Sınıf II-III için 70-360 m³ civarındadır.',
                ''],
            ['kirli-su-dalgic-pompa', 'faq',
                ' (Horoz Electric gibi markalarda bulabilirsiniz)',
                ''],
        ];
    }

    public function up(): void
    {
        foreach ($this->rewrites() as [$slug, $oldDescriptionMd5, $newDescription, $oldFaqMd5, $newFaq]) {
            $category = Category::query()->where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            $changes = [];
            if (md5((string) $category->getRawOriginal('description')) === $oldDescriptionMd5) {
                $changes['description'] = $newDescription;
            }
            if (md5((string) $category->getRawOriginal('faq')) === $oldFaqMd5) {
                $changes['faq'] = $newFaq;
            }

            if ($changes !== []) {
                $category->forceFill($changes)->save();
            }
        }

        foreach (collect($this->replacements())->groupBy(0) as $slug => $items) {
            $category = Category::query()->where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            $description = (string) $category->getRawOriginal('description');
            $faq = is_array($category->faq) ? $category->faq : [];
            $changes = [];

            foreach ($items as [, $field, $old, $new]) {
                if ($field === 'description' && str_contains($description, $old)) {
                    $description = str_replace($old, $new, $description);
                    $changes['description'] = $description;
                }

                if ($field === 'faq') {
                    foreach ($faq as $index => $item) {
                        foreach (['q', 'a'] as $key) {
                            if (isset($item[$key]) && is_string($item[$key]) && str_contains($item[$key], $old)) {
                                $faq[$index][$key] = str_replace($old, $new, $item[$key]);
                                $changes['faq'] = $faq;
                            }
                        }
                    }
                }
            }

            if ($changes !== []) {
                $category->forceFill($changes)->save();
            }
        }
    }

    public function down(): void
    {
        // İçerik düzeltmesi; geri alma için yedekten dönülür.
    }
};
