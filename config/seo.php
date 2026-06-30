<?php

/**
 * SEO hedefleri, anahtar kelimeler ve sayfa meta şablonları.
 * Rakip referans: kampa.com.tr (Havalandırma ve Sulama Sistemi)
 */
return [
    'homepage' => [
        'title' => 'Su Pompası, Hidrofor, Dalgıç Pompa ve Vantilatör',
        'h1' => 'Su Pompası, Hidrofor, Dalgıç Pompa ve Vantilatör',
        'description' => 'Su pompası, hidrofor, dalgıç pompa, santrifüj pompa ve sanayi vantilatörü modelleri. Pedrollo, Sumak, Winpo, Etna garantili ürünler. Ücretsiz teknik danışmanlık, hızlı kargo.',
    ],

    /**
     * Birincil hedef anahtar kelimeler (içerik ve meta optimizasyonu için).
     * @var list<string>
     */
    'primary_keywords' => [
        'su pompası',
        'hidrofor',
        'dalgıç pompa',
        'santrifüj pompa',
        'vantilatör',
        'sanayi tipi vantilatör',
        'jet pompa',
        'frekans kontrollü hidrofor',
        'drenaj pompası',
        'sirkülasyon pompası',
    ],

    /**
     * Marka odaklı hedef kelimeler (marka sayfaları + ürün title).
     * @var list<string>
     */
    'brand_keywords' => [
        'pedrollo',
        'sumak',
        'winpo',
        'etna',
        'ebara',
        'kaysu',
        'kosar',
        'grundfos',
        'wilo',
    ],

    'brand_page_title_suffix' => 'Ürünleri ve Fiyatları',

    'sitemap_cache_seconds' => (int) env('SEO_SITEMAP_CACHE_SECONDS', 3600),
    'robots_cache_seconds' => (int) env('SEO_ROBOTS_CACHE_SECONDS', 86400),
];
