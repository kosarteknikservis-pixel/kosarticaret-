<?php

/**
 * Kalıcı yönlendirmeler (301). Eski site yolu => yeni site yolu (path only).
 * Örnek: '/shop/urun/eski-slug' => '/urun/yeni-slug'
 *
 * Not: /kategori/* ve /urun-kategori/* önce LegacyRedirectResolver tarafından çözülür;
 * buradaki kayıtlar yalnızca resolver null döndüğünde devreye girer (yedek / tutarlılık).
 */
return [
    '/blog/en-iyi-hidrofor-markalari-2024' => '/blog/en-iyi-hidrofor-markalari-2026',

    // Kırık backlink düzeltmeleri (DataForSEO audit 2026-08-19)
    '/hidrofor-pompasi' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    '/en-uygun-hidroforu-nasil-secersiniz' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    '/en-uygun-hidroforu-nasil-secersiniz/' => '/kategoriler/hidrofor-sistemleri/hidroforlar',

    // Eski WooCommerce kategori URL'leri (www subdomain + trailing slash)
    '/urun-kategori/yedek-parca' => '/urunler',
    '/urun-kategori/yedek-parca/' => '/urunler',
    '/urun-kategori/bahce-yapi-market/dalgic-pompa/drenaj-dalgic-pompalari' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
    '/urun-kategori/bahce-yapi-market/dalgic-pompa/drenaj-dalgic-pompalari/' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
    '/urun-kategori/bahce-yapi-market/genlesme-tanklari' => '/kategoriler/hidrofor-sistemleri',
    '/urun-kategori/bahce-yapi-market/genlesme-tanklari/' => '/kategoriler/hidrofor-sistemleri',

    // kosar.net.tr'den gelen /kategori/ URL'leri (kanonik tam yol — zincir yok)
    '/kategori/hidroforlar' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    '/kategori/genlesme-tanklari' => '/kategoriler/hidrofor-sistemleri',
    '/kategori/yangin-hidroforlari' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari',
    '/kategori/dalgic-pompalar' => '/kategoriler/su-pompalari/dalgic-pompalar',
    '/kategori/sirkulasyon-pompalari' => '/kategoriler/su-pompalari/sirkulasyon-pompalari',
    '/kategori/yedek-parca' => '/urunler',

    // Eski ürün URL'leri (kaldırılmış, artık 404/500 veriyor)
    '/urun/tekli-led-isikli-anahtar' => '/urunler',
    '/urun/tekli-led-isikli-anahtar/' => '/urunler',
    '/urun/amerikan-fis' => '/urunler',
    '/urun/amerikan-fis/' => '/urunler',
];
