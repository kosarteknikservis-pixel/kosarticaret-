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
            'subtitle' => 'Hidrofor nedir, ne işe yarar? Ev, apartman ve sanayi hidrofor grupları — Pedrollo, Sumak, frekans kontrollü sistemler.',
            'buying_guide' => <<<'HTML'
<h3>Hidrofor Nedir? Sistem Nasıl Seçilir?</h3>
<p><strong>Hidrofor</strong>, pompa, basınç tankı ve otomatik kontrolün birlikte çalıştığı su basınçlandırma sistemidir. Düşük şebeke basıncı, üst katlarda zayıf su ve depo beslemeli apartmanlarda hidrofor sistemi suyu konforlu basınca çıkarır. Detaylı anlatım: <a href="/blog/hidrofor-nedir-ne-ise-yarar-nasil-calisir">hidrofor nedir, ne işe yarar?</a> · <a href="/blog/apartman-icin-hidrofor-nasil-secilir">apartman hidrofor seçimi</a></p>
<p>Doğru <strong>hidrofor seçimi</strong> için <strong>kat/daire sayısı</strong>, <strong>eşzamanlı musluk</strong> ve <strong>su kaynağı debisi</strong> hesaplanmalıdır.</p>
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
    ],
];
