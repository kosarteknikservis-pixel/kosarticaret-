<?php

/**
 * SEO hedefleri, anahtar kelimeler ve sayfa meta şablonları.
 * Rakip referans: kampa.com.tr (Havalandırma ve Sulama Sistemi)
 */
return [
    'homepage' => [
        'title' => 'Su Pompası, Hidrofor, Dalgıç Pompa ve Vantilatör',
        'h1' => 'Su Pompası, Hidrofor, Dalgıç Pompa ve Vantilatör',
        'description' => 'Koşar Ticaret: Su pompası, hidrofor, dalgıç pompa, santrifüj pompa ve sanayi vantilatörü. Pedrollo, Sumak, Winpo, Etna yetkili satıcısı. Ücretsiz teknik danışmanlık, hızlı kargo.',
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
        'sıcak su sirkülasyon pompası',
        'yangın pompası',
        'foseptik pompa',
        'derin kuyu pompası',
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

    'indexing' => [
        'indexnow_endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
        'google_enabled' => filter_var(env('GOOGLE_INDEXING_ENABLED', false), FILTER_VALIDATE_BOOL),
        'google_credentials' => env('GOOGLE_INDEXING_CREDENTIALS', ''),
        'max_urls_per_batch' => (int) env('INDEXING_MAX_URLS', 100),
        'queue' => filter_var(env('INDEXING_USE_QUEUE', false), FILTER_VALIDATE_BOOL),
    ],

    /**
     * llms.txt — GEO / AI keşfi için küratörlü referanslar (B2 + ticari kategori önceliği).
     *
     * @var array{
     *     featured_category_paths: list<string>,
     *     featured_blog_slugs: list<string>,
     *     recent_blog_limit: int
     * }
     */
    'llms' => [
        'featured_category_paths' => [
            'su-pompalari/dalgic-pompalar',
            'su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
            'su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa',
            'su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa',
            'hidrofor-sistemleri/hidroforlar',
            'hidrofor-sistemleri/ev-tipi-hidroforlar',
            'hidrofor-sistemleri/frekans-kontrollu-hidroforlar',
            'hidrofor-sistemleri/hidrofor-grubu',
            'vantilatorler/sanayi-tipi-vantilator',
            'su-pompalari/sirkulasyon-pompalari',
        ],
        'featured_blog_slugs' => [
            'hidrofor-fiyatlari-2026-ev-apartman',
            'hidrofor-nedir-ne-ise-yarar-nasil-calisir',
            'dalgic-pompa-nedir-ne-ise-yarar-nasil-secilir',
            'sumak-pompa-marka-rehberi',
            'sanayi-tipi-vantilator-secimi-rehberi',
            'sicak-su-sirkulasyon-pompasi-secimi',
            'pedrollo-sumak-hidrofor-karsilastirma',
            'ev-tipi-hidrofor-rehberi-mustakil-ev-villa',
            'kuyu-dalgic-pompa-secimi-derinlik-rehberi',
            'en-iyi-hidrofor-markalari-2026',
        ],
        'recent_blog_limit' => 10,
    ],
];
