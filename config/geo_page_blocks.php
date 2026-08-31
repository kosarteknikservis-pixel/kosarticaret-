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
                'title' => 'Sumak pompa hangi ihtiyaç için?',
                'headers' => ['İhtiyaç', 'Sumak serisi / tip', 'Not'],
                'rows' => [
                    ['Ev basınçlandırma', 'SKS / SKT hidrofor', 'Ekonomik paket'],
                    ['Kuyu / sarnıç', 'Sumak dalgıç', 'Derinliğe göre model'],
                    ['Sulama / transfer', 'Jet / santrifüj', 'Yüzey montaj'],
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
    ],
];
