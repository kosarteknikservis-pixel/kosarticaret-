<?php

/**
 * GEO Bölüm 2 — kısa cevap, fiyat bandı ve seçim tablosu (AI alıntı optimizasyonu).
 * Fiyat aralıkları katalog orientasyonudur; güncel fiyat ürün kartlarındadır.
 */
return [
    'categories' => [
        'hidrofor-sistemleri' => [
            'short_answer' => 'Hidrofor sistemi, şebeke basıncının yetersiz kaldığı ev, apartman ve işletmelerde pompa + basınç tankı + otomatik kontrol ile sabit su basıncı sağlayan paket çözümdür. Doğru seçim kat sayısı, eşzamanlı musluk sayısı ve su kaynağına göre yapılır.',
            'price_band' => [
                'from' => 2500,
                'to' => 120000,
                'currency' => 'TRY',
                'note' => 'Ev tipi paketlerden çok pompalı apartman gruplarına kadar; motor gücü, tank hacmi ve markaya göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Detaylı hidrofor fiyat rehberi (2026)',
                'url' => '/blog/hidrofor-fiyatlari-2026-ev-apartman',
            ],
            'selection_table' => [
                'title' => 'Kaç katlı binaya hangi hidrofor tipi?',
                'headers' => ['Kullanım', 'Kat / daire', 'Önerilen tip', 'Tank / güç'],
                'rows' => [
                    ['Müstakil ev', '1–2 kat', 'Ev tipi paket hidrofor', '24–50 L · 0,5–1,1 kW'],
                    ['Apartman', '3–6 kat', 'Frekans kontrollü veya grup', '50–100 L · 1,5–2,2 kW'],
                    ['Site / işyeri', '7+ kat', 'Hidrofor grubu', 'Çok pompalı · 3 kW+'],
                ],
            ],
        ],

        'su-pompalari/dalgic-pompalar' => [
            'short_answer' => 'Dalgıç pompa, motor ve pompa gövdesinin sıvı içinde çalıştığı, emme hattı gerektirmeyen pompa tipidir. Temiz su kuyusu, drenaj, foseptik ve derin sondaj uygulamalarında kullanılır; seçimde debi (m³/s), basma yüksekliği (m) ve partikül geçiş çapı (mm) belirleyicidir.',
            'price_band' => [
                'from' => 3500,
                'to' => 95000,
                'currency' => 'TRY',
                'note' => 'Temiz su kuyu pompasından foseptik/drenaj modellerine; derinlik ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Hangi uygulamada hangi dalgıç pompa?',
                'headers' => ['Uygulama', 'Su tipi', 'Kritik parametre', 'Alt kategori'],
                'rows' => [
                    ['Kuyu / sarnıç', 'Temiz', 'Kuyu derinliği, debi', 'Derin kuyu / temiz su'],
                    ['Bodrum drenajı', 'Yağmur suyu', 'Partikül mm, otomatik float', 'Drenaj dalgıç'],
                    ['Foseptik / atık', 'Kirli', 'Bıçaklı / flatörlü', 'Foseptik dalgıç'],
                ],
            ],
        ],

        'vantilatorler/sanayi-tipi-vantilator' => [
            'short_answer' => 'Sanayi tipi vantilatör, atölye, depo, fabrika ve tünel gibi yüksek hacimli alanlarda toz, duman ve ısı tahliyesi için kullanılan yüksek debili aksiyel veya santrifüj fan grubudur. Seçimde debi (m³/h), statik basınç (Pa) ve motor IP koruma sınıfı esas alınır.',
            'price_band' => [
                'from' => 4500,
                'to' => 45000,
                'currency' => 'TRY',
                'note' => 'Duvar/ayaklı modellerden uzaktan kumandalı büyük çaplı modellere; çap ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Sanayi vantilatörü hızlı seçim',
                'headers' => ['Alan tipi', 'Tercih edilen tip', 'Debi ipucu'],
                'rows' => [
                    ['Atölye / garaj', 'Duvar veya ayaklı aksiyel', '5.000–15.000 m³/h'],
                    ['Depo / hangar', 'Büyük çaplı aksiyel', '20.000 m³/h+'],
                    ['Tozlu ortam', 'Metal kanat, IP55+', 'Filtre + hız kontrol'],
                ],
            ],
        ],

        'hidrofor-sistemleri/hidroforlar' => [
            'short_answer' => 'Hidrofor, pompa + basınç tankı + otomatik kontrol birleşimidir; şebeke veya depo basıncı yetmediğinde musluklarda sabit basınç sağlar. Ev tipi tek pompalı paketler ile apartman için frekans kontrollü veya çok pompalı gruplar aynı kategoride değerlendirilir; seçim kat/daire ve debiye göre yapılır.',
            'price_band' => [
                'from' => 2500,
                'to' => 90000,
                'currency' => 'TRY',
                'note' => 'Tek pompalı ev paketinden orta boy apartman sistemlerine; kW ve tank hacmine göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Kaç katlı binaya hangi hidrofor?',
                'url' => '/blog/kac-katli-binaya-hangi-hidrofor',
            ],
            'selection_table' => [
                'title' => 'Hidrofor tipi hızlı seçim',
                'headers' => ['İhtiyaç', 'Tip', 'Tipik güç'],
                'rows' => [
                    ['1–2 kat müstakil', 'Ev tipi paket', '0,5–1,1 kW'],
                    ['3–6 kat apartman', 'Frekans / güçlü paket', '1,5–2,2 kW'],
                    ['Yoğun eşzamanlı kullanım', 'Hidrofor grubu', 'Çok pompalı'],
                ],
            ],
        ],

        'hidrofor-sistemleri/ev-tipi-hidroforlar' => [
            'short_answer' => 'Ev tipi hidrofor, müstakil ev, villa ve küçük apartmanlarda musluk basıncını yükseltmek için kullanılan kompakt pompa + tank paketidir. Tipik seçim 0,5–1,1 kW motor ve 24–50 L tank ile yapılır; bahçe sulaması eklenecekse debi ihtiyacı ayrıca hesaplanır.',
            'price_band' => [
                'from' => 2500,
                'to' => 25000,
                'currency' => 'TRY',
                'note' => 'Kompakt paketlerden villa tipi güçlü modellere; marka ve tank hacmine göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Ev tipi hidrofor fiyat ve seçim rehberi',
                'url' => '/blog/hidrofor-fiyatlari-2026-ev-apartman',
            ],
            'selection_table' => [
                'title' => 'Ev tipi hidrofor ne zaman yeterli?',
                'headers' => ['Senaryo', 'Öneri', 'Not'],
                'rows' => [
                    ['1 daire / 1–2 kat', '0,5–0,75 kW · 24 L', 'Kompakt paket'],
                    ['Villa / 2–3 kat', '0,75–1,1 kW · 50 L', 'Bahçe ayrı hesap'],
                    ['Çok daire / 5+ kat', 'Apartman grubu', 'Ev tipi yetmez'],
                ],
            ],
        ],

        'hidrofor-sistemleri/hidrofor-grubu' => [
            'short_answer' => 'Hidrofor grubu, birden fazla pompanın ortak manifold ve kontrol panosu ile çalıştığı sistemdir; site, otel ve yüksek katlı binalarda eşzamanlı debiyi karşılamak için tercih edilir. Yedek pompa, frekans kontrolü ve kademeli devreye girme enerji ve süreklilik sağlar.',
            'price_band' => [
                'from' => 15000,
                'to' => 120000,
                'currency' => 'TRY',
                'note' => 'İki pompalı gruplardan endüstriyel çok pompalı sistemlere; kW ve panel özelliğine göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Otel / site hidrofor grubu seçimi',
                'url' => '/blog/otel-site-hidrofor-grubu-secimi',
            ],
            'selection_table' => [
                'title' => 'Hidrofor grubu ne zaman gerekir?',
                'headers' => ['Kullanım', 'Pompa sayısı', 'Kontrol'],
                'rows' => [
                    ['Küçük site', '2 pompa', 'Basınç şalteri / VFD'],
                    ['Otel / orta site', '2–3 pompa', 'Frekans + yedek'],
                    ['Yüksek kat / yoğun', '3+ pompa', 'Kademeli + izleme'],
                ],
            ],
        ],

        'su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa' => [
            'short_answer' => 'Derin kuyu dalgıç pompa, sondaj veya derin kuyuda su seviyesinin altında çalışan, yüksek basma yüksekliği (H) üreten pompadır. Seçimde kuyu derinliği, dinamik su seviyesi, debi (m³/h) ve kuyu çapı (inç) esas alınır; emiş hattı gerektirmez.',
            'price_band' => [
                'from' => 4500,
                'to' => 95000,
                'currency' => 'TRY',
                'note' => '4\" konut kuyusundan derin endüstriyel modellere; metre ve kW\'a göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Kuyu dalgıç pompa derinlik rehberi',
                'url' => '/blog/kuyu-dalgic-pompa-secimi-derinlik-rehberi',
            ],
            'selection_table' => [
                'title' => 'Derin kuyu dalgıç hızlı seçim',
                'headers' => ['Derinlik bandı', 'Tipik güç', 'Kritik kontrol'],
                'rows' => [
                    ['0–30 m', '0,5–1,1 kW', 'Debi + kablo kesiti'],
                    ['30–80 m', '1,1–3 kW', 'Dinamik seviye'],
                    ['80 m+', '3 kW+', 'İnce gövde / verim'],
                ],
            ],
        ],

        'su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa' => [
            'short_answer' => 'Foseptik dalgıç pompa, atık su ve çamurlu sıvıyı tahliye etmek için bıçaklı veya geniş geçişli gövdeyle tasarlanır; temiz su kuyu pompası ile karıştırılmamalıdır. Seçimde partikül geçiş çapı (mm), flatör ihtiyacı ve basma mesafesi belirleyicidir.',
            'price_band' => [
                'from' => 4000,
                'to' => 45000,
                'currency' => 'TRY',
                'note' => 'Ev tipi flatörlü modellerden bıçaklı endüstriyel tahliyeye; mm geçiş ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Foseptik dalgıç neye göre seçilir?',
                'headers' => ['Uygulama', 'Kritik özellik', 'Not'],
                'rows' => [
                    ['Ev foseptiği', 'Flatör + orta mm', 'Otomatik seviye'],
                    ['Yoğun katı', 'Bıçaklı gövde', 'Tıkanma riskini düşürür'],
                    ['Uzun basma hattı', 'Yüksek H (m)', 'Boru kaybı hesapla'],
                ],
            ],
        ],

        'su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa' => [
            'short_answer' => 'Drenaj dalgıç pompa, bodrum, yağmur suyu çukuru ve şantiye drenajında temiz veya hafif kirli suyu tahliye eder; foseptik kadar agresif katı taşımaz. Seçimde partikül mm, otomatik float ve saatlik debi ihtiyacı öne çıkar.',
            'price_band' => [
                'from' => 3500,
                'to' => 35000,
                'currency' => 'TRY',
                'note' => 'Kompakt bodrum modellerinden paslanmaz şantiye drenajına; debi ve malzemeye göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Drenaj dalgıç hızlı seçim',
                'headers' => ['Alan', 'Su tipi', 'Öneri'],
                'rows' => [
                    ['Bodrum çukuru', 'Yağmur / sızıntı', 'Float + 10–20 mm'],
                    ['Bahçe / havuz kenarı', 'Temiz-kirli karışık', 'Paslanmaz opsiyon'],
                    ['Şantiye', 'Çamurlu su', 'Yüksek debi / dayanıklı gövde'],
                ],
            ],
        ],

        'su-pompalari/sirkulasyon-pompalari' => [
            'short_answer' => 'Sirkülasyon pompası, ısıtma veya sıcak kullanım suyu hattında düşük basma yüksekliğiyle sürekli veya zamanlı devridaim yapar; bina basıncını artırmaz. Seçimde boru çapı, hat uzunluğu, sıvı sıcaklığı ve enerji sınıfı (ör. frekans kontrollü) kontrol edilir.',
            'price_band' => [
                'from' => 1800,
                'to' => 12000,
                'currency' => 'TRY',
                'note' => 'Standart rekorlu modellerden flanşlı / frekans kontrollü modellere; bağlama tipine göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Sıcak su sirkülasyon pompası seçimi',
                'url' => '/blog/sicak-su-sirkulasyon-pompasi-secimi',
            ],
            'selection_table' => [
                'title' => 'Sirkülasyon pompası tipi',
                'headers' => ['Bağlantı', 'Kullanım', 'Not'],
                'rows' => [
                    ['Rekorlu dişli', 'Konut hattı', 'Kolay montaj'],
                    ['Inline', 'Kompakt hatlar', 'Dar alan'],
                    ['Flanşlı', 'Merkezi sistem', 'Yüksek debi'],
                ],
            ],
        ],

        'su-pompalari/santrifuj-pompalar' => [
            'short_answer' => 'Santrifüj pompa, dönen fanın oluşturduğu merkezkaç kuvvetiyle sıvıyı basan yüzey montajlı pompadır; sulama, transfer ve endüstriyel proseslerde yaygındır. Seçimde debi, basma yüksekliği, emme yüksekliği (NPSH) ve sıvı sıcaklığı/temizliği esas alınır; emiş hattında priming kritik olabilir.',
            'price_band' => [
                'from' => 3000,
                'to' => 55000,
                'currency' => 'TRY',
                'note' => 'Tek fanlı konut modellerinden çift fanlı / endüstriyel modellere; kW ve gövde malzemesine göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Santrifüj emme ve priming rehberi',
                'url' => '/blog/santrifuj-pompa-emme-priming-rehberi',
            ],
            'selection_table' => [
                'title' => 'Santrifüj pompa hızlı seçim',
                'headers' => ['Uygulama', 'Tip', 'Dikkat'],
                'rows' => [
                    ['Sulama / transfer', 'Tek fanlı', 'Emme yüksekliği'],
                    ['Yüksek basınç', 'Çift fanlı', 'Debi-basınç dengesi'],
                    ['Kirli sıvı', 'Özel gövde', 'Standart temiz su değil'],
                ],
            ],
        ],

        'su-pompalari/jet-pompalar-derinden-emisli' => [
            'short_answer' => 'Jet (derinden emişli) pompa, ejektör yardımıyla yüzeyden daha derin kuyu veya sarnıçtan su çekmek için kullanılır; klasik santrifüje göre emme kapasitesi yüksektir. Tek/çift ejektör seçimi derinlik ve debiye göre yapılır; priming ve emme hattı sızdırmazlığı kritiktir.',
            'price_band' => [
                'from' => 3200,
                'to' => 28000,
                'currency' => 'TRY',
                'note' => 'Bahçe/jet paketlerinden derin emişli modellere; ejektör tipi ve kW\'a göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Tek / çift ejektörlü jet pompa farkı',
                'url' => '/blog/tek-cift-ejektorlu-jet-pompa-farki',
            ],
            'selection_table' => [
                'title' => 'Jet pompa ne zaman tercih edilir?',
                'headers' => ['Senaryo', 'Ejektör', 'Not'],
                'rows' => [
                    ['Sığ kuyu / sarnıç', 'Tek ejektör', 'Bahçe sulama'],
                    ['Daha derin emiş', 'Çift ejektör', 'Debi düşebilir'],
                    ['Çok derin kuyu', 'Dalgıç tercih', 'Jet sınırı aşılır'],
                ],
            ],
        ],
    ],

    'brands' => [
        'sumak' => [
            'short_answer' => 'Sumak, Türkiye\'de üretilen yerli bir pompa markasıdır; hidrofor (SKS/SKT), dalgıç, jet ve santrifüj segmentlerinde bütçe odaklı projeler için yaygın tercih edilir. Yedek parça ve servis erişimi güçlüdür; premium dayanıklılık için Pedrollo alternatif olarak değerlendirilir.',
            'price_band' => [
                'from' => 2800,
                'to' => 90000,
                'currency' => 'TRY',
                'note' => 'Ev tipi hidrofordan endüstriyel dalgıç gruplarına; seri ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Sumak seri karşılaştırması (SKS / SKT / dalgıç)',
                'headers' => ['Seri / grup', 'Kullanım', 'Tank / güç', 'Not'],
                'rows' => [
                    ['SKS hidrofor', 'Müstakil ev, yazlık', '24–50 L · 0,5–1 HP', 'Kompakt paket'],
                    ['SKT hidrofor', 'Apartman, yoğun kullanım', '50–100 L · 1–2 HP', 'Yüksek debi'],
                    ['SSP-INV', 'Konfor + enerji tasarrufu', 'Frekans kontrollü', 'Sessiz çalışma'],
                    ['Sumak dalgıç', 'Kuyu, drenaj, foseptik', 'Derinliğe göre kW', 'Temiz / kirli su'],
                    ['Jet / santrifüj', 'Sulama, transfer', 'Yüzey montaj', 'Ekonomik segment'],
                ],
            ],
        ],

        'pedrollo' => [
            'short_answer' => 'Pedrollo, İtalya menşeli premium pompa markasıdır; derin kuyu dalgıç, santrifüj ve hidrofor uygulamalarında yüksek verim, uzun ömür ve düşük arıza oranı arayan projeler için tercih edilir. Fiyat Sumak\'a göre yüksektir; kritik ve sürekli çalışan sistemlerde amortisman avantajı sağlar.',
            'price_band' => [
                'from' => 5500,
                'to' => 150000,
                'currency' => 'TRY',
                'note' => '4" kuyu dalgıçtan çok pompalı hidrofora; model ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Pedrollo ne zaman tercih edilir?',
                'headers' => ['Senaryo', 'Pedrollo avantajı', 'Alternatif'],
                'rows' => [
                    ['Derin kuyu (50 m+)', 'Yüksek verim, ince gövde', 'Sumak derin kuyu'],
                    ['Sürekli çalışma', 'Endüstriyel dayanım', 'Winpo orta segment'],
                    ['Apartman grubu', 'Düşük enerji tüketimi', 'Frekans kontrollü grup'],
                ],
            ],
        ],

        'winpo' => [
            'short_answer' => 'Winpo, konut ve hafif ticari projelerde jet, santrifüj ve dalgıç segmentlerinde orta fiyat / performans dengesi sunan markadır. Pedrollo kadar premium olmayan, Sumak\'tan biraz daha üst konumlanan uygulamalarda sık tercih edilir; seçimde debi-basma eğrisi ve garanti şartları kontrol edilmelidir.',
            'price_band' => [
                'from' => 3500,
                'to' => 70000,
                'currency' => 'TRY',
                'note' => 'Jet ve yüzey pompalarından orta boy dalgıç modellere; seriye göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Winpo ne zaman uygun?',
                'headers' => ['Senaryo', 'Avantaj', 'Alternatif'],
                'rows' => [
                    ['Bahçe / jet emiş', 'Orta segment fiyat', 'Sumak jet'],
                    ['Konut dalgıç', 'Dengeli performans', 'Pedrollo premium'],
                    ['Transfer / sulama', 'Yüzey montaj', 'Santrifüj alternatif'],
                ],
            ],
        ],

        'kaysu' => [
            'short_answer' => 'Kaysu, özellikle sirkülasyon ve konut pompa uygulamalarında bütçe odaklı yerli seçenek sunar. Sıcak su devridaimi ve ekonomik hidrofor/dalgıç ihtiyaçlarında değerlendirilir; kritik sürekli işletmede premium markalarla karşılaştırma yapılmalıdır.',
            'price_band' => [
                'from' => 1800,
                'to' => 40000,
                'currency' => 'TRY',
                'note' => 'Sirkülasyon modellerinden ekonomik dalgıç/hidrofor paketlerine; modele göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Sıcak su sirkülasyon pompası seçimi',
                'url' => '/blog/sicak-su-sirkulasyon-pompasi-secimi',
            ],
            'selection_table' => [
                'title' => 'Kaysu hangi iş için?',
                'headers' => ['Uygulama', 'Tip', 'Not'],
                'rows' => [
                    ['Sıcak su hattı', 'Sirkülasyon', 'Timer / enerji sınıfı'],
                    ['Bütçe konut', 'Ekonomik pompa', 'Yoğun endüstriyel değil'],
                    ['Yedek / geçici', 'Hızlı tedarik', 'Premium alternatifle kıyasla'],
                ],
            ],
        ],

        'kosar' => [
            'short_answer' => 'Koşar, pompa ve hidrofor sistemlerinde satış + teknik danışmanlık + montaj desteği sunan yerli marka/operatördür. Yangın, hidrofor grubu ve proje bazlı seçimlerde ürün tedariki ile saha tecrübesini birleştirir; nihai model seçimi debi-basınç hesabına göre yapılır.',
            'price_band' => [
                'from' => 3000,
                'to' => 120000,
                'currency' => 'TRY',
                'note' => 'Konut paketlerinden proje tipi yangın/hidrofor gruplarına; kapsam ve kW\'a göre değişir.',
            ],
            'guide_cta' => [
                'label' => 'Ücretsiz teknik danışmanlık',
                'url' => '/iletisim',
            ],
            'selection_table' => [
                'title' => 'Koşar desteği hangi aşamada?',
                'headers' => ['Aşama', 'Ne sağlanır?', 'Sonuç'],
                'rows' => [
                    ['Seçim', 'Debi / basınç danışmanlığı', 'Doğru model'],
                    ['Tedarik', 'Stok ve alternatif marka', 'Hızlı teslim'],
                    ['Kurulum sonrası', 'Teknik destek', 'Arıza azaltma'],
                ],
            ],
        ],
    ],

    'blog' => [
        'hidrofor-fiyatlari-2026-ev-apartman' => [
            'short_answer' => 'Hidrofor fiyatları 2026\'da ev tipi paketlerde yaklaşık 2.500–18.000 TL, apartman ve grup sistemlerinde 15.000–120.000 TL bandında değişir. Fiyatı motor gücü (kW), tank hacmi (L), pompa sayısı ve frekans invertörü belirler; güncel tutar ürün sayfasındadır.',
            'price_band' => [
                'from' => 2500,
                'to' => 120000,
                'currency' => 'TRY',
                'note' => 'Montaj, elektrik ve boru maliyeti fiyata dahil değildir.',
            ],
            'selection_table' => [
                'title' => 'Ev ve apartman hidrofor fiyat segmentleri',
                'headers' => ['Segment', 'Tipik kullanım', 'Fiyat orientasyonu'],
                'rows' => [
                    ['Ev tipi paket', '1 daire / müstakil', '2.500–15.000 TL'],
                    ['Villa / büyük ev', '2–3 kat, bahçe', '8.000–25.000 TL'],
                    ['Apartman grubu', 'Çok daire', '15.000–120.000 TL'],
                ],
            ],
        ],

        'dalgic-pompa-nedir-ne-ise-yarar-nasil-secilir' => [
            'short_answer' => 'Dalgıç pompa, sıvı içinde çalışan ve emme hattı olmadan su basan pompadır; kuyu, sarnıç, drenaj ve foseptik tahliyesinde kullanılır. Seçimde kuyu derinliği, debi (m³/h), partikül çapı (mm) ve mono/trifaze besleme kontrol edilir.',
            'price_band' => [
                'from' => 3500,
                'to' => 95000,
                'currency' => 'TRY',
                'note' => 'Temiz su kuyu pompası ile foseptik/drenaj arasında geniş aralık; derinlik ve kW belirler.',
            ],
            'selection_table' => [
                'title' => 'Dalgıç pompa türleri karşılaştırması',
                'headers' => ['Tür', 'Ne işe yarar?', 'Seçim kriteri'],
                'rows' => [
                    ['Temiz su', 'Kuyu, sarnıç', 'Debi + basma (m)'],
                    ['Drenaj', 'Bodrum, yağmur suyu', 'Partikül geçiş (mm)'],
                    ['Foseptik', 'Atık su', 'Bıçaklı / flatörlü'],
                ],
            ],
        ],

        'sumak-pompa-marka-rehberi' => [
            'short_answer' => 'Sumak pompa; hidrofor, dalgıç, jet ve santrifüj gruplarında yerli üretim ve geniş servis ağı sunan markadır. Bütçe odaklı konut ve tarım projelerinde tercih edilir; derin kuyu ve sürekli endüstriyel işletmede Pedrollo ile karşılaştırma yapılmalıdır.',
            'price_band' => [
                'from' => 2800,
                'to' => 90000,
                'currency' => 'TRY',
                'note' => 'SKS/SKT hidrofordan endüstriyel dalgıç gruplarına; stoktaki modele göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Sumak pompa serileri ne için?',
                'headers' => ['Seri / tip', 'Uygulama', 'Güç aralığı'],
                'rows' => [
                    ['SKS / SKT hidrofor', 'Ev, apartman', '0,5–2,2 kW'],
                    ['Sumak dalgıç', 'Kuyu, drenaj', '0,5–7,5 kW'],
                    ['Jet / santrifüj', 'Sulama, transfer', '0,5–3 kW'],
                ],
            ],
        ],

        'sanayi-tipi-vantilator-secimi-rehberi' => [
            'short_answer' => 'Sanayi tipi vantilatör seçiminde alan hacmi (m³), saatte kaç hava değişimi istendiği ve kanal direnci hesaplanır; sonuç debi (m³/h) ve basınç (Pa) değerini verir. Duvar, ayaklı ve kanal tipi montaj; toz yoğunluğuna göre IP koruma sınıfı seçilir.',
            'price_band' => [
                'from' => 4500,
                'to' => 45000,
                'currency' => 'TRY',
                'note' => '24" ayaklı modellerden 30" uzaktan kumandalı modellere; çap ve motor gücüne göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Fabrika / depo vantilatör seçimi',
                'headers' => ['Alan', 'Önerilen debi', 'Montaj'],
                'rows' => [
                    ['100–300 m² atölye', '8.000–15.000 m³/h', 'Duvar veya ayaklı'],
                    ['500 m²+ depo', '25.000 m³/h+', 'Büyük çap aksiyel'],
                    ['Tozlu üretim', 'Filtreli sistem', 'IP55+ motor'],
                ],
            ],
        ],

        'sicak-su-sirkulasyon-pompasi-secimi' => [
            'short_answer' => 'Sıcak su sirkülasyon pompası, kombi veya su ısıtıcısından uzak musluklara sıcak suyu hızlı taşımak için devridaim hattında düşük debiyle çalışır; basınç artırmaz. Seçimde boru çapı, hat uzunluğu, sıvı sıcaklığı (°C) ve zamanlayıcı ihtiyacı esas alınır.',
            'price_band' => [
                'from' => 1800,
                'to' => 12000,
                'currency' => 'TRY',
                'note' => 'Standart sirkülasyondan frekans kontrollü ve ısıya dayanıklı modellere; markaya göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Sıcak su sirkülasyon pompası hızlı seçim',
                'headers' => ['Hat uzunluğu', 'Önerilen debi', 'Ek özellik'],
                'rows' => [
                    ['0–20 m', '1–2 m³/h', 'Timer opsiyonel'],
                    ['20–40 m', '2–4 m³/h', 'Wilo/Grundfos sınıfı'],
                    ['Merkezi sistem', 'Frekans kontrollü', 'Enerji tasarrufu'],
                ],
            ],
        ],

        'pedrollo-sumak-hidrofor-karsilastirma' => [
            'short_answer' => 'Pedrollo İtalyan üretim, premium dayanıklılık ve sessizlik; Sumak yerli üretim, uygun fiyat ve geniş servis ağı sunar. Ev ve bütçe odaklı projelerde Sumak; uzun ömür ve düşük gürültü önceliğinde Pedrollo tercih edilir.',
            'price_band' => [
                'from' => 2800,
                'to' => 150000,
                'currency' => 'TRY',
                'note' => 'Sumak ev tipinden Pedrollo grup sistemlerine; model ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Pedrollo mu Sumak hidrofor mu?',
                'headers' => ['Kriter', 'Pedrollo', 'Sumak'],
                'rows' => [
                    ['Fiyat segmenti', 'Premium', 'Ekonomik / orta'],
                    ['Sessizlik', 'Yüksek', 'Modele göre'],
                    ['Servis', 'Distribütör ağı', 'Yerli, hızlı'],
                ],
            ],
        ],

        'dalgic-pompa-vs-hidrofor-farklari' => [
            'short_answer' => 'Dalgıç pompa suyun içinde çalışır; kuyu, drenaj ve foseptik için kullanılır. Hidrofor pompa + tank + basınç kontrolü ile bina içi su basıncını sabitler. Aynı amaç için değil, farklı uygulama senaryoları içindir.',
            'price_band' => [
                'from' => 2500,
                'to' => 120000,
                'currency' => 'TRY',
                'note' => 'Dalgıç pompa ve hidrofor ayrı kalemler; birlikte kurulumda toplam bütçe artar.',
            ],
            'selection_table' => [
                'title' => 'Dalgıç pompa mı hidrofor mu?',
                'headers' => ['İhtiyaç', 'Tercih', 'Neden'],
                'rows' => [
                    ['Kuyudan su çekme', 'Dalgıç pompa', 'Emiş hattı gerekmez'],
                    ['Apartman basıncı', 'Hidrofor', 'Tank + otomatik kontrol'],
                    ['Bodrum drenajı', 'Dalgıç (drenaj)', 'Kirli su / float'],
                ],
            ],
        ],

        'kac-katli-binaya-hangi-hidrofor' => [
            'short_answer' => '3 katlı müstakil ve küçük apartmanlarda ev tipi paket hidrofor (0,5–1,1 kW); 5–6 katta frekans kontrollü veya 1,5–2,2 kW grup; 10 kat ve üzeri site yapılarında çok pompalı hidrofor grubu gerekir. Kesin seçim daire sayısı ve debi hesabı ile yapılır.',
            'price_band' => [
                'from' => 2500,
                'to' => 120000,
                'currency' => 'TRY',
                'note' => 'Kat ve daire arttıkça motor gücü, tank ve pompa sayısı artar.',
            ],
            'selection_table' => [
                'title' => 'Kat sayısına göre hidrofor tipi',
                'headers' => ['Kat / daire', 'Tip', 'Motor / tank'],
                'rows' => [
                    ['1–3 kat', 'Ev tipi paket', '0,5–1,1 kW · 24–50 L'],
                    ['3–6 kat', 'Frekans / grup', '1,5–2,2 kW · 50–100 L'],
                    ['7–10+ kat', 'Hidrofor grubu', 'Çok pompalı · 3 kW+'],
                ],
            ],
        ],

        'en-iyi-dalgic-pompa-markasi-rehberi' => [
            'short_answer' => 'Tek bir en iyi dalgıç pompa markası yoktur; kuyu derinliği, su kalitesi ve bütçeye göre Pedrollo (premium), Sumak (yerli/ekonomik), Wilo ve Grundfos (endüstriyel) değerlendirilir. Seçimde debi, basma yüksekliği ve garanti esas alınır.',
            'price_band' => [
                'from' => 3500,
                'to' => 95000,
                'currency' => 'TRY',
                'note' => 'Temiz su kuyusundan foseptik/drenaj modellerine; derinlik ve kW\'a göre değişir.',
            ],
            'selection_table' => [
                'title' => 'Dalgıç pompa marka segmentleri',
                'headers' => ['Segment', 'Markalar', 'Kullanım'],
                'rows' => [
                    ['Premium', 'Pedrollo, Grundfos', 'Derin kuyu, sürekli işletme'],
                    ['Orta', 'Winpo, Alarko', 'Konut, orta derinlik'],
                    ['Bütçe', 'Sumak, Kaysu', 'Ekonomik proje, yerli servis'],
                ],
            ],
        ],

        'hidrofor-kurulumu-montaj-rehberi' => [
            'short_answer' => 'Hidrofor montajında emiş/basma hatları sızdırmaz olmalı, tank ön şarjı (pre-charge) doğru ayarlanmalı ve elektrik beslemesi motor plakasına uygun seçilmelidir. Yanlış emiş yüksekliği, hava kaçağı ve hatalı basınç şalteri ayarı en sık arıza kaynaklarıdır.',
            'selection_table' => [
                'title' => 'Hidrofor kurulum kontrol listesi',
                'headers' => ['Adım', 'Kontrol', 'Risk'],
                'rows' => [
                    ['Hortum / boru', 'Sızdırmazlık + vana', 'Hava emme'],
                    ['Tank', 'Ön şarj basıncı', 'Sık aç-kapa'],
                    ['Elektrik', 'Sigorta / faz', 'Motor yanması'],
                ],
            ],
            'guide_cta' => [
                'label' => 'Hidrofor ürünlerini incele',
                'url' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
            ],
        ],

        'dalgic-pompa-kablo-baglantisi-kesit-secimi' => [
            'short_answer' => 'Dalgıç pompa kablo kesiti, motor gücü (kW/HP), besleme mesafesi ve gerilim düşümü limitine göre seçilir; ince kablo ısınma ve tork kaybına yol açar. Bağlantıda su geçirmez ek, doğru faz sırası ve topraklama zorunludur.',
            'selection_table' => [
                'title' => 'Kablo kesiti seçiminde öncelik',
                'headers' => ['Faktör', 'Etki', 'Pratik not'],
                'rows' => [
                    ['Motor kW', 'Akım (A) artar', 'Plaka değerini esas al'],
                    ['Kablo boyu', 'Gerilim düşümü', 'Uzun hatta kesit büyüt'],
                    ['Ek / konektör', 'Su girişi riski', 'IP68 ek kullan'],
                ],
            ],
            'guide_cta' => [
                'label' => 'Dalgıç pompa kategorisi',
                'url' => '/kategoriler/su-pompalari/dalgic-pompalar',
            ],
        ],
    ],
];
