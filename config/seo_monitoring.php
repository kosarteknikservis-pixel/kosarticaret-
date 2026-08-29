<?php

/**
 * SEO izleme: drift taramasi, legacy redirect dogrulama, GSC kategori kelime takibi.
 */
return [
    /*
    | Drift baseline/check URL listesi (path). Deploy sonrasi regresyon kontrolu.
    */
    'drift_urls' => [
        ['key' => 'home', 'path' => '/'],
        ['key' => 'categories_index', 'path' => '/kategoriler'],
        ['key' => 'category_su_pompalari', 'path' => '/kategoriler/su-pompalari'],
        ['key' => 'category_hidrofor', 'path' => '/kategoriler/hidrofor-sistemleri'],
        ['key' => 'category_dalgic', 'path' => '/kategoriler/su-pompalari/dalgic-pompalar'],
        ['key' => 'blog_index', 'path' => '/blog'],
        ['key' => 'robots', 'path' => '/robots.txt'],
        ['key' => 'sitemap', 'path' => '/sitemap.xml'],
    ],

    /*
    | Legacy URL → beklenen kanonik path (301 zinciri ve yanlis hedef kontrolu).
    */
    'legacy_redirect_checks' => [
        ['from' => '/kategori/dalgic-pompalar', 'expected_path' => '/kategoriler/su-pompalari/dalgic-pompalar'],
        ['from' => '/urun-kategori/dalgic-pompalar', 'expected_path' => '/kategoriler/su-pompalari/dalgic-pompalar'],
        ['from' => '/urun-kategori/yangin-pompalari', 'expected_path' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari'],
        ['from' => '/urun-kategori/kademeli-pompalar', 'expected_path' => '/kategoriler/su-pompalari/kademeli-pompalar'],
    ],

    /*
    | GSC performans importunda takip edilecek ticari kelimeler (haftalik).
    | target_position: hedef ortalama pozisyon (dusuk = daha iyi).
    */
    'category_keywords' => [
        ['keyword' => 'hidrofor', 'target_position' => 10],
        ['keyword' => 'hidrofor fiyatları', 'target_position' => 10],
        ['keyword' => 'dalgıç pompa', 'target_position' => 10],
        ['keyword' => 'dalgıç pompa fiyatları', 'target_position' => 10],
        ['keyword' => 'su pompası', 'target_position' => 15],
        ['keyword' => 'santrifüj pompa', 'target_position' => 15],
        ['keyword' => 'kademeli pompa', 'target_position' => 15],
        ['keyword' => 'vantilatör', 'target_position' => 15],
        ['keyword' => 'endüstriyel vantilatör', 'target_position' => 15],
        ['keyword' => 'yangın pompası', 'target_position' => 20],
        ['keyword' => 'sirkülasyon pompası', 'target_position' => 20],
        ['keyword' => 'jakuzi pompası', 'target_position' => 20],
    ],

    'drift_baseline_path' => storage_path('seo-reports/drift-baseline.json'),
    'drift_report_path' => storage_path('seo-reports/drift-check-latest.json'),
    'redirect_report_path' => storage_path('seo-reports/redirect-check-latest.json'),
];
