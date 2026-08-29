<?php

/**
 * Tamamlayıcı kategori eşleşmeleri (slug → ilgili slug listesi).
 * Ürün veya kategori, kendi slug'ı ya da ata slug'ı eşleşince bu gruplar gösterilir.
 * Katalogda olmayan slug'lar runtime'da atlanır.
 */
return [
    'cross_sell_by_slug' => [
        'su-pompalari' => [
            'dalgic-pompalar',
            'hidrofor-sistemleri',
            'santrifuj-pompalar',
            'sirkulasyon-pompalari',
        ],
        'pompalar' => [
            'dalgic-pompalar',
            'hidroforlar',
            'santrifuj-pompalar',
        ],
        'dalgic-pompalar' => [
            'hidromat',
            'hidroforlar',
            'derin-kuyu-dalgic-pompa',
        ],
        'derin-kuyu-dalgic-pompa' => [
            'hidromat',
            'hidrofor-sistemleri',
            'temiz-su-dalgic-pompasi',
        ],
        'temiz-su-dalgic-pompasi' => [
            'derin-kuyu-dalgic-pompa',
            'hidromat',
        ],
        'hidroforlar' => [
            'hidromat',
            'sicak-su-hidroforu',
            'hidrofor-grubu',
            'ev-tipi-hidroforlar',
        ],
        'hidrofor-sistemleri' => [
            'hidromat',
            'sicak-su-hidroforu',
            'frekans-kontrollu-hidroforlar',
            'ev-tipi-hidroforlar',
        ],
        'ev-tipi-hidroforlar' => [
            'hidromat',
            'sicak-su-hidroforu',
        ],
        'hidrofor-grubu' => [
            'frekans-kontrollu-hidroforlar',
            'hidromat',
        ],
        'foseptik-dalgic-pompa' => [
            'bicakli-dalgic-pompa',
            'drenaj-dalgic-pompa',
        ],
        'drenaj-dalgic-pompa' => [
            'foseptik-dalgic-pompa',
            'kirli-su-dalgic-pompa',
        ],
        'santrifuj-pompalar' => [
            'jet-pompalar-derinden-emisli',
            'sirkulasyon-pompalari',
        ],
        'jet-pompalar-derinden-emisli' => [
            'hidroforlar',
            'santrifuj-pompalar',
        ],
        'sirkulasyon-pompalari' => [
            'sicak-su-hidroforu',
            'inline-sirkulasyon-pompalari',
        ],
        'vantilatorler' => [
            'sanayi-tipi-vantilator',
        ],
        'fan-ve-aspirator' => [
            'sanayi-tipi-vantilator',
            'vantilatorler',
        ],
    ],

    /*
    | B2 hedef rehberler: marka/kategori slug → blog slug listesi (yayında olanlar).
    */
    'blog_guides_by_brand_slug' => [
        'sumak' => [
            'sumak-pompa-marka-rehberi',
            'sumak-hidrofor-sks-skt-serileri',
            'pedrollo-sumak-hidrofor-karsilastirma',
        ],
        'pedrollo' => [
            'pedrollo-pompa-marka-rehberi',
            'pedrollo-sumak-hidrofor-karsilastirma',
        ],
        'kaysu' => [
            'kaysu-pompa-marka-rehberi',
        ],
    ],

    'blog_guides_by_category_slug' => [
        'sanayi-tipi-vantilator' => [
            'sanayi-tipi-vantilator-secimi-rehberi',
            'depo-fabrika-havalandirma-rehberi',
            'vantilator-debi-hesabi-ach-rehberi',
        ],
        'dalgic-pompalar' => [
            'dalgic-pompa-nedir-ne-ise-yarar-nasil-secilir',
            'dalgic-pompa-kurulum-ipuclari',
            'kuyu-dalgic-pompa-secimi-derinlik-rehberi',
        ],
        'sirkulasyon-pompalari' => [
            'sicak-su-sirkulasyon-pompasi-secimi',
            'sirkulasyon-pompasi-nedir-nasil-secilir',
        ],
        'hidrofor-sistemleri' => [
            'hidrofor-fiyatlari-2026-ev-apartman',
            'hidrofor-nedir-ne-ise-yarar-nasil-calisir',
            'ev-tipi-hidrofor-rehberi-mustakil-ev-villa',
        ],
        'hidroforlar' => [
            'hidrofor-fiyatlari-2026-ev-apartman',
            'hidrofor-kurulumu-montaj-rehberi',
        ],
        'vantilatorler' => [
            'sanayi-tipi-vantilator-secimi-rehberi',
            'vantilator-nedir-nasil-secilir',
        ],
    ],
];
