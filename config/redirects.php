<?php

/**
 * Kalıcı yönlendirmeler (301). Eski site yolu => yeni site yolu (path only).
 * Örnek: '/shop/urun/eski-slug' => '/urun/yeni-slug'
 *
 * Not: /kategori/* ve /urun-kategori/* önce LegacyRedirectResolver tarafından çözülür;
 * buradaki kayıtlar yalnızca resolver null döndüğünde devreye girer (yedek / tutarlılık).
 *
 * yedek-parca / genlesme-tanklari / Alarko WSD → legacy_redirects.not_found (404).
 */
return [
    '/blog/en-iyi-hidrofor-markalari-2024' => '/blog/en-iyi-hidrofor-markalari-2026',

    // Kırık backlink düzeltmeleri (DataForSEO audit 2026-08-19)
    '/hidrofor-pompasi' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    '/en-uygun-hidroforu-nasil-secersiniz' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    '/en-uygun-hidroforu-nasil-secersiniz/' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    // Eşdeğer 200 hedefler (2026-11-20 hidrofor paket) — homepage yok.
    '/marka/Sumak' => '/marka/sumak',
    '/kategoriler/su-pompalari/jet-pompalar' => '/kategoriler/su-pompalari/jet-pompalar-derinden-emisli',
    '/blog/presostat-histerezis-ayari-start-stop' => '/blog/hidrofor-presostat-histerezis-set-noktalari',
    '/blog/hidrofor-start-stop-salinimi-tank-presostat' => '/blog/hidrofor-start-stop-salinimi-kisa-cevrim',

    // Eski WooCommerce kategori URL'leri (www subdomain + trailing slash)
    '/urun-kategori/bahce-yapi-market/dalgic-pompa/drenaj-dalgic-pompalari' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
    '/urun-kategori/bahce-yapi-market/dalgic-pompa/drenaj-dalgic-pompalari/' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',

    // kosar.net.tr'den gelen /kategori/ URL'leri (kanonik tam yol — zincir yok)
    '/kategori/hidroforlar' => '/kategoriler/hidrofor-sistemleri/hidroforlar',
    '/kategori/yangin-hidroforlari' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari',
    '/kategori/dalgic-pompalar' => '/kategoriler/su-pompalari/dalgic-pompalar',
    '/kategori/sirkulasyon-pompalari' => '/kategoriler/su-pompalari/sirkulasyon-pompalari',

    // Eski WooCommerce mağaza kökleri (404 → 301; alt yollar resolver'da)
    '/magaza' => '/kategoriler',
    '/shop' => '/kategoriler',
    '/urun-kategori' => '/kategoriler',

    // Eski ürün URL'leri (kaldırılmış, artık 404/500 veriyor)
    '/urun/tekli-led-isikli-anahtar' => '/urunler',
    '/urun/tekli-led-isikli-anahtar/' => '/urunler',
    '/urun/amerikan-fis' => '/urunler',
    '/urun/amerikan-fis/' => '/urunler',
];
