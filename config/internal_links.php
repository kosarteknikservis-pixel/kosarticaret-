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
];
