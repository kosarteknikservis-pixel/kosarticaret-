<?php

/**
 * Kategori landing — satın alma rehberi, alt başlık ve güven unsurları.
 * DB'de buying_guide boşsa bu içerik kullanılır; seo:seed-buying-guides ile DB'ye yazılır.
 */
return [
    'default_trust' => [
        ['icon' => 'shield', 'label' => 'Orijinal ürün garantisi'],
        ['icon' => 'truck', 'label' => '1.000 TL üzeri ücretsiz kargo'],
        ['icon' => 'phone', 'label' => 'Ücretsiz teknik danışmanlık'],
        ['icon' => 'shield', 'label' => '2 yıl üretici garantisi'],
    ],

    'landings' => [
        'su-pompalari' => [
            'subtitle' => '1.000\'i aşkın su pompası modeli — Pedrollo, Sumak, Winpo ve Ebara garantili. Ücretsiz teknik danışmanlık ve hızlı teslimat.',
            'buying_guide' => <<<'HTML'
<h3>Su Pompası Nasıl Seçilir?</h3>
<p>Doğru pompa seçimi için dört parametreyi netleştirin: <strong>debi (Q, m³/saat)</strong>, <strong>basma yüksekliği (H, metre)</strong>, <strong>sıvı tipi</strong> (temiz, kirli, kimyasal) ve <strong>kurulum yeri</strong> (yüzey veya su altı). Bu değerler belirlendikten sonra santrifüj, dalgıç, kademeli veya hidrofor yolu netleşir.</p>
<h3>Pompa Tipi Karşılaştırması</h3>
<table>
<thead><tr><th>Tip</th><th>En İyi Kullanım</th><th>Avantaj</th></tr></thead>
<tbody>
<tr><td>Santrifüj</td><td>Sulama, proses, bina tesisatı</td><td>Yüksek debi, kolay bakım</td></tr>
<tr><td>Dalgıç</td><td>Kuyu, drenaj, foseptik</td><td>Emme hattı yok, yüksek verim</td></tr>
<tr><td>Kademeli</td><td>Yüksek basınç hatları</td><td>Çok kademeli basınç artışı</td></tr>
<tr><td>Hidrofor</td><td>Ev / apartman basınçlı su</td><td>Otomatik basınç, sabit debi</td></tr>
</tbody>
</table>
<p>Marka seçiminde <a href="/marka/pedrollo">Pedrollo</a> (İtalya, premium), <a href="/marka/sumak">Sumak</a> (yerli, ekonomik) ve <a href="/marka/winpo">Winpo</a> (jet/hidrofor) en çok tercih edilen markalarımızdır. Kararsızsanız WhatsApp veya telefon hattımızdan ücretsiz boyutlandırma alabilirsiniz.</p>
HTML,
        ],

        'su-pompalari/dalgic-pompalar' => [
            'subtitle' => 'Temiz su, drenaj, foseptik, derin kuyu ve kirli su dalgıç pompaları — IP68 koruma, garantili markalar.',
            'buying_guide' => <<<'HTML'
<h3>Dalgıç Pompa Seçim Rehberi</h3>
<p>Dalgıç pompa seçiminde önce <strong>su kaynağı</strong> ve <strong>partikül boyutu</strong> belirlenir. Temiz su uygulamalarında gıda uyumlu malzeme; drenaj ve foseptikte geçirebilir parçacık çapı (mm) kritiktir.</p>
<ul>
<li><strong>Temiz su:</strong> Keson kuyu, sarnıç — <a href="/kategoriler/su-pompalari/dalgic-pompalar/temiz-su-dalgic-pompasi">temiz su dalgıç pompası</a></li>
<li><strong>Derin kuyu (8 m+):</strong> Sondaj/artesyen — <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a></li>
<li><strong>Bodrum/drenaj:</strong> Yağmur suyu — <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompa</a></li>
<li><strong>Foseptik:</strong> Atık su — <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompa</a> veya <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompa</a></li>
</ul>
<p>Derin kuyu pompalarında kuyu iç çapı (4" / 6") pompa gövde çapından büyük olmalıdır. Susuz çalışma koruması (seviye şamandırası veya basınç anahtarı) mutlaka kullanılmalıdır.</p>
HTML,
        ],

        'su-pompalari/santrifuj-pompalar' => [
            'subtitle' => 'Tek fanlı, çift fanlı, paslanmaz ve salyangoz santrifüj pompalar — sulama ve endüstriyel proses için.',
            'buying_guide' => <<<'HTML'
<h3>Santrifüj Pompa Seçim Kriterleri</h3>
<p>Santrifüj pompalar yüzeye monte edilir; emme yüksekliği pratikte 7–8 metreyi geçmemelidir. Seçimde <strong>fan sayısı</strong> (basınç), <strong>gövde malzemesi</strong> (döküm, paslanmaz) ve <strong>motor gücü</strong> belirleyicidir.</p>
<ul>
<li><strong>Tek fanlı:</strong> Orta basınç, genel sulama — ekonomik</li>
<li><strong>Çift fanlı:</strong> Daha yüksek basınç, çok katlı binalar</li>
<li><strong>Paslanmaz:</strong> Kimyasal, gıda, havuz kloru</li>
<li><strong>Salyangoz:</strong> Yüksek debi, düşük basınç sulama hatları</li>
</ul>
<p>Jet pompa alternatifleri düşük debili kuyularda kullanılabilir; 35 m üzeri derinlikte <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a> verimlilik açısından üstündür.</p>
HTML,
        ],

        'hidrofor-sistemleri' => [
            'subtitle' => 'Pedrollo, Sumak ve Winpo hidrofor modelleri — ev, apartman ve sanayi için stoktan teslim, teknik seçim desteği.',
            'buying_guide' => <<<'HTML'
<h3>Hidrofor Sistemi Seçim Rehberi</h3>
<p>Ev, apartman ve işletmeler için doğru hidrofor modeli; <strong>kat/daire sayısı</strong>, <strong>eşzamanlı kullanım</strong>, su deposunun konumu ve gerekli debiye göre belirlenir. Teknik çalışma prensibi için <a href="/blog/hidrofor-nedir-ne-ise-yarar-nasil-calisir">detaylı teknik rehberi</a>, apartman uygulamaları için <a href="/blog/apartman-icin-hidrofor-nasil-secilir">apartman seçim rehberini</a> inceleyin.</p>
<p>Model seçerken pompa gücü, tank hacmi, kontrol tipi ve servis erişimini birlikte değerlendirin; ekibimiz kullanım bilgilerinize göre uygun kapasiteyi belirlemenize yardımcı olur.</p>
<ul>
<li><strong>1–2 katlı ev:</strong> 24–50 L tanklı ev tipi hidrofor (0,75–1,1 kW)</li>
<li><strong>3–6 katlı apartman:</strong> Frekans kontrollü veya büyük tanklı grup</li>
<li><strong>Site / sanayi:</strong> Çok pompalı hidrofor grubu</li>
</ul>
<p>Marka rehberi: <a href="/blog/en-iyi-hidrofor-markalari-2026">en iyi hidrofor markaları</a> · <a href="/kategoriler/hidrofor-sistemleri/frekans-kontrollu-hidroforlar">Frekans kontrollü hidroforlar</a> · <a href="/pompa-secici">Pompa Seçici</a></p>
HTML,
        ],

        'hidrofor-sistemleri/hidroforlar' => [
            'subtitle' => 'Ev tipi ve çok katlı bina hidroforları — hazır paket sistemler, montaja hazır.',
            'buying_guide' => <<<'HTML'
<h3>Ev Tipi Hidrofor Rehberi</h3>
<p>Ev tipi hidrofor seçerken pompa debisi, tank hacmi ve maksimum basınç değerlerini birlikte değerlendirin. Jet pompalı hidroforlar sığ kuyularda; santrifüj pompalı gruplar yüksek debi gerektiren binalarda tercih edilir.</p>
<p>Tank hacmi ne kadar büyükse pompa o kadar seyrek devreye girer; motor ömrü uzar. Konutlar için genellikle <strong>24 L veya 50 L</strong> tank yeterlidir. Basınç anahtarı açma/kapama basınçlarının doğru ayarlanması su kesintisi ve aşırı devreye girmeyi önler.</p>
HTML,
        ],

        'su-pompalari/sirkulasyon-pompalari' => [
            'subtitle' => 'Kalorifer, yerden ısıtma ve sıcak su devridaim sistemleri için sirkülasyon pompası modelleri.',
            'buying_guide' => <<<'HTML'
<h3>Sirkülasyon Pompası Seçim Rehberi</h3>
<p>Sirkülasyon pompası seçiminde <strong>tesisat tipi</strong>, gerekli <strong>debi</strong>, basma yüksekliği ve akışkan sıcaklığı birlikte değerlendirilmelidir. Kalorifer, yerden ısıtma ve kullanım sıcak suyu devridaim hatları aynı pompa tipiyle değerlendirilmemelidir.</p>
<ul>
<li><strong>Kalorifer / yerden ısıtma:</strong> Isıtma devresinin hat direncine ve kazan kapasitesine uygun sirkülasyon pompası</li>
<li><strong>Sıcak su devridaim:</strong> Sıcaklığa dayanıklı, düşük debili ve zaman ayarlı model</li>
<li><strong>Frekans kontrollü:</strong> Değişken debili sistemlerde daha düşük enerji tüketimi</li>
</ul>
<p>Muslukta beklemeden sıcak su için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/sicak-su-pompalari">sıcak su pompalarını</a> inceleyin. Çalışma prensibi ve doğru kullanım için <a href="/blog/sirkulasyon-pompasi-nedir-nasil-secilir">teknik rehber</a> yardımcı olur.</p>
HTML,
        ],

        'su-pompalari/sirkulasyon-pompalari/sicak-su-pompalari' => [
            'subtitle' => 'Sıcak su devridaim hatları için ısıya dayanıklı sirkülasyon pompaları — hızlı sıcak su ve düşük su israfı.',
            'buying_guide' => <<<'HTML'
<h3>Sıcak Su Sirkülasyon Pompası Nasıl Seçilir?</h3>
<p>Sıcak su sirkülasyon pompası, uzun boru hatlarında musluğu açtığınızda sıcak suya ulaşma süresini azaltan devridaim çözümüdür. Seçimde <strong>hat uzunluğu</strong>, <strong>sıvı sıcaklığı</strong>, bağlantı çapı ve zamanlayıcı ihtiyacı önemlidir.</p>
<ul>
<li><strong>Sıcaklık uyumu:</strong> Pompa gövdesi, conta ve çark kullanım sıcaklığına uygun olmalıdır.</li>
<li><strong>Debi / basma:</strong> Gereğinden büyük pompa gürültü ve gereksiz enerji tüketimi oluşturabilir.</li>
<li><strong>Timer:</strong> Sabah ve akşam kullanım saatlerinde çalıştırmak enerji kaybını azaltır.</li>
</ul>
<p>Sıcak su devridaim pompası basınç artırmaz; düşük basınç sorunu için ayrı bir <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforu</a> gerekebilir. Uygulama detayları için <a href="/blog/sicak-su-sirkulasyon-pompasi-secimi">sıcak su sirkülasyon pompası rehberini</a> inceleyin.</p>
HTML,
        ],

        'su-pompalari/ozel-amacli-pompalar/yangin-pompalari' => [
            'subtitle' => 'Elektrikli, dizel ve jockey yangın pompası sistemleri — proje debisi ve basıncına göre teknik seçim desteği.',
            'buying_guide' => <<<'HTML'
<h3>Yangın Pompası Seçim Kriterleri</h3>
<p>Yangın pompası; sprinkler, hidrant ve yangın dolabı hatlarında proje debisini ve basıncını sağlayacak şekilde seçilmelidir. Konut hidroforundan farklı olarak <strong>yangın projesi</strong>, risk sınıfı ve ilgili standartlar esas alınır.</p>
<ul>
<li><strong>Debi ve basma yüksekliği:</strong> Hidrolik hesap ve tesisat kayıplarına göre belirlenir.</li>
<li><strong>Yedeklilik:</strong> Elektrikli ana pompa, dizel yedek pompa ve jockey pompa ihtiyacı projeye göre değerlendirilir.</li>
<li><strong>Kurulum:</strong> Kontrol panosu, yangın suyu deposu ve test hattı yetkili uygulama firmasıyla planlanmalıdır.</li>
</ul>
<p>Yangın pompası türleri ve seçim adımları için <a href="/blog/yangin-pompasi-nedir-nasil-secilir">yangın pompası teknik rehberini</a> inceleyin. Proje değerlerinizle <a href="/iletisim">teknik teklif</a> isteyebilirsiniz.</p>
HTML,
        ],

        'su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa' => [
            'subtitle' => 'Sondaj ve artezyen kuyular için 4 inç ve 6 inç derin kuyu dalgıç pompa modelleri — debi ve basma yüksekliğine göre seçim.',
            'buying_guide' => <<<'HTML'
<h3>Derin Kuyu Pompası Seçim Rehberi</h3>
<p>Derin kuyu dalgıç pompa seçimi yalnızca kuyu derinliğine göre yapılmaz. <strong>Statik su seviyesi</strong>, dinamik seviye, istenen debi, toplam basma yüksekliği ve kuyu çapı birlikte hesaplanmalıdır.</p>
<ul>
<li><strong>Kuyu çapı:</strong> 4 inç ve 6 inç kuyu çapına uygun gövde seçilmelidir.</li>
<li><strong>Debi:</strong> Sulama hattı veya konut tüketiminin saatlik ihtiyacı belirlenmelidir.</li>
<li><strong>Basma yüksekliği:</strong> Su seviyesi, kot farkı ve boru kayıpları hesaplamaya eklenmelidir.</li>
<li><strong>Koruma:</strong> Susuz çalışma ve voltaj dalgalanmasına karşı pano/koruma ekipmanı kullanılmalıdır.</li>
</ul>
<p>Derinlik ve debi hesabının ayrıntıları için <a href="/blog/kuyu-dalgic-pompa-secimi-derinlik-rehberi">derin kuyu seçim rehberini</a> okuyun. Ürün fiyatlarını model özellikleriyle birlikte karşılaştırın.</p>
HTML,
        ],

        'su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa' => [
            'subtitle' => 'Foseptik, atık su ve drenaj uygulamaları için dalgıç pompa modelleri — partikül geçişi ve basma yüksekliğine göre seçim.',
            'buying_guide' => <<<'HTML'
<h3>Foseptik Dalgıç Pompa Nasıl Seçilir?</h3>
<p>Foseptik pompa seçiminde aktarılacak sıvının yapısı, katı partikül çapı, çukur derinliği ve basma hattı uzunluğu belirleyicidir. Lifli ve yoğun atık içeren uygulamalarda <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompa</a> daha uygun olabilir.</p>
<ul>
<li><strong>Partikül geçişi:</strong> Pompa çarkının geçirebildiği maksimum katı parça çapını kontrol edin.</li>
<li><strong>Basma yüksekliği:</strong> Çukur derinliği, yatay hat ve dirsek kayıplarını birlikte hesaplayın.</li>
<li><strong>Şamandıra:</strong> Otomatik devreye girme ve kuru çalışma riskini azaltmak için tercih edilir.</li>
</ul>
<p>Pompa yerine tanklı tahliye sistemi arıyorsanız <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/foseptik-tahliye-cihazi">foseptik tahliye cihazlarını</a> inceleyin. Uygulama seçimi için <a href="/blog/foseptik-dalgic-pompa-secimi-rehberi">foseptik pompa rehberi</a> yardımcı olur.</p>
HTML,
        ],

        'vantilatorler' => [
            'subtitle' => 'Ev tipi ve sanayi tipi vantilatörler — depo, fabrika ve atölye havalandırma çözümleri.',
            'buying_guide' => <<<'HTML'
<h3>Vantilatör Seçim Rehberi</h3>
<p>Havalandırma ihtiyacı <strong>hacim (m³)</strong> ve saatte kaç kez hava değişimi (ACH) ile hesaplanır. Depo ve fabrikalarda genellikle 6–10 ACH hedeflenir.</p>
<ul>
<li><strong>Duvar tipi aksiyel:</strong> Orta debi, kolay montaj</li>
<li><strong>Sanayi tipi:</strong> Yüksek debi, sürekli çalışma</li>
<li><strong>Kanal tipi:</strong> HVAC entegrasyonu</li>
</ul>
<p>Motor koruması (IP55+), balanslı kanat ve düşük gürültü seviyesi uzun ömürlü kullanım için kritiktir. <a href="/kategoriler/vantilatorler/sanayi-tipi-vantilator">Sanayi tipi vantilatör</a> modellerimizi karşılaştırabilirsiniz.</p>
HTML,
        ],

        'vantilatorler/sanayi-tipi-vantilator' => [
            'subtitle' => 'Yüksek debili sanayi vantilatörleri — fabrika, depo, atölye ve tünel havalandırma.',
            'buying_guide' => <<<'HTML'
<h3>Sanayi Tipi Vantilatör Seçimi</h3>
<p>Sanayi ortamlarında sıcaklık, toz yoğunluğu ve sürekli çalışma süresi motor ve kanat seçimini belirler. Metal kanatlı modeller aşınmaya dayanıklıdır; korozif ortamlarda paslanmaz gövde tercih edilir.</p>
<p>Debi (m³/h) ve statik basınç (Pa) değerleri fan eğrisi üzerinde çalışma noktanızı gösterir. Yanlış boyutlandırma enerji israfı ve yetersiz havalandırmaya yol açar — teknik ekibimiz alan ölçünüze göre model önerir.</p>
HTML,
        ],

        'su-pompalari/kademeli-pompalar' => [
            'subtitle' => 'Dikey, yatay ve monoblok kademeli pompalar — yüksek basınçlı bina ve proses hatları.',
            'buying_guide' => <<<'HTML'
<h3>Kademeli Pompa Ne Zaman Tercih Edilir?</h3>
<p>Tek kademeli pompaların basma yüksekliği yetersiz kaldığında (genellikle 80 m+) kademeli pompalar devreye girer. Her kademe (aşama) basıncı artırır; aynı debide çok daha yüksek toplam yükseklik elde edilir.</p>
<p>Dikey kademeli modeller yer tasarrufu sağlar; yatay kademeli modeller servis erişimi kolaydır. Yangın sistemleri, yüksek bina tesisatı ve RO besleme hatlarında yaygın kullanılır.</p>
HTML,
        ],

        'su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa' => [
            'subtitle' => 'Bodrum, otopark ve inşaat sahası drenajı için drenaj dalgıç pompalar — partikül geçişi ve debi seçimi rehberi.',
            'description' => <<<'HTML'
<p>Drenaj dalgıç pompa; bodrum katları, otoparklar, inşaat çukurları, teras ve bahçe drenaj hatlarında biriken yağmur suyunu ve kirli sıvıyı hızlıca tahliye etmek için kullanılır. Yüzey pompalarından farklı olarak pompa gövdesi sıvı içinde çalışır; emme hattı ve emme problemi olmadan yüksek debi sağlar.</p>
<p>Seçimde üç parametre belirleyicidir: <strong>partikül geçiş çapı</strong> (mm), <strong>debi (m³/saat)</strong> ve <strong>basma yüksekliği</strong>. Temiz yağmur suyu uygulamalarında 10–35 mm partikül geçişi yeterli olurken; inşaat ve endüstriyel drenajda daha büyük geçişli modeller tercih edilir. Otomatik devreye girme için float şamandıra veya seviye sensörü kullanılmalıdır.</p>
<p>Koşar Ticaret'te Sumak, Pedrollo ve Winpo marka drenaj dalgıç pompa modellerini stoktan ve proje bazlı seçim desteğiyle sunuyoruz. Benzer uygulamalar için <a href="/kategoriler/su-pompalari/dalgic-pompalar/paslanmaz-drenaj-dalgic-pompa">paslanmaz drenaj pompalarını</a> ve <a href="/kategoriler/su-pompalari/dalgic-pompalar/sintine-pompasi">sintine pompalarını</a> karşılaştırabilirsiniz.</p>
HTML,
            'buying_guide' => <<<'HTML'
<h3>Drenaj Dalgıç Pompa Seçim Rehberi</h3>
<p>Drenaj pompası seçerken tahliye edilecek suyun kirlilik derecesi, çukur derinliği ve basma hattı uzunluğu birlikte hesaplanmalıdır.</p>
<ul>
<li><strong>Partikül geçişi:</strong> Temiz drenaj için 10–20 mm; inşaat/kirli su için 35 mm ve üzeri.</li>
<li><strong>Debi:</strong> Alan büyüklüğü ve yağış yoğunluğuna göre m³/saat ihtiyacı belirlenir.</li>
<li><strong>Basma yüksekliği:</strong> Çukur derinliği + yatay hat kayıpları + çıkış kotu farkı.</li>
<li><strong>Otomatik çalışma:</strong> Float veya elektronik seviye anahtarı susuz çalışma riskini azaltır.</li>
</ul>
<p>Foseptik ve atık su uygulamalarında <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompa</a> veya lifli atık için <a href="/kategoriler/su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa">bıçaklı dalgıç pompa</a> modellerine bakın. Teknik detay için <a href="/blog/drenaj-pompasi-nedir-nasil-secilir">drenaj pompası seçim rehberini</a> inceleyin.</p>
HTML,
            'faq' => [
                ['q' => 'Drenaj pompası ile foseptik pompası arasındaki fark nedir?', 'a' => 'Drenaj pompası genellikle yağmur suyu ve hafif kirli sıvılar içindir; partikül geçişi sınırlıdır. Foseptik pompalar atık su ve çözeltili sıvılar için tasarlanır; daha geniş partikül geçişi ve farklı malzeme seçimi sunar.'],
                ['q' => 'Bodrum drenaj pompası ne kadar debi gerekir?', 'a' => 'Ortalama konut bodrumu için 5–15 m³/saat yeterli olabilir; otopark ve geniş alanlarda 20 m³/saat üzeri modeller değerlendirilmelidir. Yağış yoğunluğu ve alan m² değerine göre hesap yapılması önerilir.'],
                ['q' => 'Drenaj pompası sürekli çalışmalı mı?', 'a' => 'Hayır. Float şamandıra veya seviye anahtarı ile su belirli seviyeye ulaştığında devreye girmesi normaldir. Sürekli çalışma genellikle kaçak, yüksek yeraltı suyu veya şamandıra arızasına işaret eder.'],
                ['q' => 'Paslanmaz drenaj pompası ne zaman gerekir?', 'a' => 'Deniz kenarı, kimyasal içerikli drenaj, gıda tesisleri veya korozif ortamlarda paslanmaz gövde ve paslanmaz parçalar tercih edilmelidir.'],
            ],
        ],

        'su-pompalari/ozel-amacli-pompalar/jakuzi-pompasi' => [
            'subtitle' => 'Jakuzi ve spa devridaim pompaları — sessiz çalışma, debi ve filtre uyumu rehberi.',
            'description' => <<<'HTML'
<p>Jakuzi pompası, spa ve jakuzi kabinlerinde suyun filtre, ısıtıcı ve jet hatları arasında sürekli devridaimini sağlayan özel amaçlı bir sirkülasyon pompasıdır. Havuz pompasından farklı olarak daha düşük debi ve sessiz çalışma önceliklidir; jet basıncı ve masaj etkisi için doğru debi seçimi konforu doğrudan etkiler.</p>
<p>Seçimde jakuzi hacmi (litre), jet sayısı, boru çapı ve mevcut filtre/ısıtıcı uyumu değerlendirilmelidir. Çok yüksek debili pompa gereksiz gürültü ve enerji tüketimine; düşük debili pompa yetersiz jet performansına yol açar. Tek fazlı (220 V) modeller ev tipi jakuzilerde yaygındır.</p>
<p>Koşar Ticaret jakuzi pompası modellerini orijinal ürün garantisi ve teknik danışmanlıkla sunar. Havuz uygulamaları için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi">ön filtreli havuz pompalarını</a>; genel dalgıç çözümler için <a href="/kategoriler/su-pompalari/dalgic-pompalar">dalgıç pompa</a> kategorisini inceleyin.</p>
HTML,
            'buying_guide' => <<<'HTML'
<h3>Jakuzi Pompası Nasıl Seçilir?</h3>
<p>Jakuzi pompası seçerken önce kabin hacmini ve üretici önerilen debi aralığını kontrol edin. Ardından bağlantı çapı, motor gücü ve sessizlik seviyesini değerlendirin.</p>
<ul>
<li><strong>Hacim uyumu:</strong> Küçük jakuzi (200–400 L) ile büyük spa (800 L+) farklı debi ister.</li>
<li><strong>Jet sayısı:</strong> Çok jetli sistemler daha yüksek debi gerektirir.</li>
<li><strong>Sessizlik:</strong> Kapalı alan kurulumlarında düşük desibel değerli modeller tercih edilir.</li>
<li><strong>Yedek parça:</strong> Conta, impeller ve motor uyumluluğu uzun vadeli bakım için önemlidir.</li>
</ul>
<p>Havuz ve jakuzi arasındaki farklar için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi">havuz pompası</a> kategorisine de göz atın.</p>
HTML,
            'faq' => [
                ['q' => 'Jakuzi pompası ile havuz pompası aynı mı?', 'a' => 'Hayır. Jakuzi pompası düşük debili devridaim için optimize edilir; havuz pompası genellikle daha yüksek debi ve filtre sistemiyle çalışır. Uygulama tipine göre doğru kategori seçilmelidir.'],
                ['q' => 'Jakuzi pompası kaç saat çalışmalı?', 'a' => 'Çoğu jakuzi günde 4–8 saat filtre/devridaim için programlanır. Kullanım sırasında jetler açıkken pompa çalışır; üretici kılavuzundaki öneriler esas alınmalıdır.'],
                ['q' => 'Jakuzi pompası gürültülü çalışıyorsa ne yapmalıyım?', 'a' => 'Hava hapsi, tıkalı filtre, yataksız montaj veya aşınmış rulman gürültüye neden olabilir. Montaj yüzeyi titreşim izolasyonu ve emme tarafı hava kaçağı kontrol edilmelidir.'],
                ['q' => 'Mevcut jakuzime uygun pompayı nasıl bulurum?', 'a' => 'Kabin marka/model bilgisi, mevcut pompa etiketindeki debi-güç değerleri ve bağlantı çapını paylaşın; teknik ekibimiz eşdeğer model önerir.'],
            ],
        ],

        'su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi' => [
            'subtitle' => 'Havuz filtrasyon ve devridaim pompaları — debi, klor dayanımı ve enerji verimliliği rehberi.',
            'description' => <<<'HTML'
<p>Ön filtreli havuz pompası, yüzme havuzlarında suyun skimmer veya bottom drain hattından emilerek filtre, ısıtıcı ve geri dönüş hattına basılmasını sağlar. Havuz hacmine uygun debi seçimi filtre kalitesi, su berraklığı ve enerji tüketimini doğrudan etkiler.</p>
<p>Seçimde havuz hacmi (m³), hedef filtre devir sayısı (genellikle günde 2–3 tur), boru çapı ve klor/kimyasal dayanımlı malzeme gereksinimi birlikte değerlendirilir. Self-priming (kendinden emişli) modeller montaj kolaylığı sağlar; yüksek verimli motorlar sezon boyunca elektrik maliyetini düşürür.</p>
<p>Pedrollo, Sumak ve Winpo havuz pompası modellerini stoktan sunuyoruz. Jakuzi uygulamaları için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/jakuzi-pompasi">jakuzi pompası</a>; genel su transferi için <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompa</a> kategorilerine bakabilirsiniz.</p>
HTML,
            'buying_guide' => <<<'HTML'
<h3>Havuz Pompası Seçim Rehberi</h3>
<p>Havuz pompası debisi, havuz hacminin günde 2–3 kez filtre edilmesini sağlayacak şekilde hesaplanır. Formül: Havuz hacmi (m³) × 3 / 24 saat ≈ saatlik debi (m³/s).</p>
<ul>
<li><strong>Debi:</strong> Küçük havuz 5–8 m³/s; orta ölçek 10–15 m³/s; büyük havuz 20 m³/s+</li>
<li><strong>Malzeme:</strong> Klor ve tuzlu su ortamlarında paslanmaz veya Noryl gövde tercih edilir</li>
<li><strong>Ön filtre:</strong> Pompa girişindeki sepet kaba partikülleri tutar; düzenli temizlik gerekir</li>
<li><strong>Basma yüksekliği:</strong> Filtre tipi, ısıtıcı ve boru kayıpları hesaba katılmalıdır</li>
</ul>
<p>Havuz pompası montajı ve bakım ipuçları için blog rehberlerimizi inceleyin; jakuzi uygulamalarında <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/jakuzi-pompasi">jakuzi pompası</a> modellerine göz atın.</p>
HTML,
            'faq' => [
                ['q' => 'Havuz pompası günde kaç saat çalışmalı?', 'a' => 'Yaz sezonunda genellikle günde 8–12 saat filtre/devridaim yeterlidir. Havuz hacmi, kullanım yoğunluğu ve sıcaklığa göre süre ayarlanmalıdır.'],
                ['q' => 'Havuz pompası debisi nasıl hesaplanır?', 'a' => 'Havuz hacmi (m³) × günlük tur sayısı (2–3) / çalışma saati formülü kullanılır. Örnek: 40 m³ havuz, günde 3 tur, 10 saat çalışma → ~12 m³/s debi.'],
                ['q' => 'Kışın havuz pompası çalıştırılmalı mı?', 'a' => 'Donma riski olan bölgelerde kışın pompa durdurulur, hatlar boşaltılır veya antifriz uygulanır. Ilıman iklimde kısa süreli devridaim devam edebilir.'],
                ['q' => 'Havuz pompası sesli çalışıyorsa sebebi ne olabilir?', 'a' => 'Hava emişi, tıkalı ön filtre sepeti, aşınmış rulman veya yanlış debi seçimi gürültüye yol açabilir. Emme hattı contası ve filtre basıncı kontrol edilmelidir.'],
            ],
        ],
    ],
];
