<?php

return [

    /*
    | Uygulama tipleri: kategori slug önceliği + isim anahtar kelimeleri.
    | Slug'lar canlı katalog yapısına göre genişletildi.
    */
    'applications' => [
        'hydrofor_apartment' => [
            'category_slugs' => [
                'ev-tipi-hidroforlar', 'hidrofor-sistemleri', 'hidroforlar', 'hidrofor-grubu',
                'sumak-hidrofor', 'pedrollo-hidrofor', 'hidromat', 'sicak-su-hidroforu',
            ],
            'name_keywords' => ['hidrofor', 'kademeli', 'paket'],
            'exclude_keywords' => ['karavan', '12 volt', '24 volt', 'tekne'],
        ],
        'hydrofor_villa' => [
            'category_slugs' => [
                'ev-tipi-hidroforlar', 'hidrofor-sistemleri', 'monoblok-yatay-kademeli',
                'yatay-kademeli-pompalar', 'kademeli-pompalar',
            ],
            'name_keywords' => ['hidrofor', 'kademeli', 'monoblok'],
            'exclude_keywords' => ['karavan', '12 volt', '24 volt'],
        ],
        'submersible_well' => [
            'category_slugs' => [
                'derin-kuyu-dalgic-pompa', 'temiz-su-dalgic-pompasi', 'dalgic-pompalar',
                'keson-kuyu-pompa', 'dalgic-pompa',
            ],
            'name_keywords' => ['dalgıç', 'dalgiç', 'derin kuyu', 'kuyu'],
            'exclude_keywords' => ['drenaj', 'kirli', 'foseptik', 'sintine', 'bicak'],
        ],
        'jet_shallow' => [
            'category_slugs' => [
                'jet-pompalar-derinden-emisli', 'su-pompalari', 'santrifuj-pompalar',
            ],
            'name_keywords' => ['jet', 'derinden emiş', 'derinden emis'],
            'exclude_keywords' => ['dalgıç', 'dalgiç', 'hidrofor'],
        ],
        'drainage' => [
            'category_slugs' => [
                'drenaj-dalgic-pompa', 'kirli-su-dalgic-pompa', 'sintine-pompasi',
                'yagmur-suyu-tahliye-pompasi', 'paslanmaz-drenaj-dalgic-pompa',
            ],
            'name_keywords' => ['drenaj', 'kirli', 'sintine', 'tahliye'],
            'exclude_keywords' => ['temiz su', 'hidrofor', 'jet'],
        ],
        'septic' => [
            'category_slugs' => [
                'foseptik-dalgic-pompa', 'foseptik-tahliye-cihazi', 'bicakli-dalgic-pompa',
            ],
            'name_keywords' => ['foseptik', 'fosa', 'bıçaklı', 'bicakli', 'öğütüc'],
            'exclude_keywords' => ['temiz', 'jet', 'hidrofor'],
        ],
        'irrigation' => [
            'category_slugs' => [
                'santrifuj-pompalar-sulama', 'santrifuj-pompalar', 'salyangoz-pompalar-bol-su-veren',
                'on-filtreli-havuz-pompasi',
            ],
            'name_keywords' => ['sulama', 'salyangoz', 'havuz', 'tarla'],
            'exclude_keywords' => ['hidrofor', 'dalgıç derin', 'sirkülasyon'],
        ],
        'circulation' => [
            'category_slugs' => [
                'sirkulasyon-pompalari', 'inline-sirkulasyon-pompalari',
                'flansli-sirkulasyon-pompalari', 'rekorlu-disli-sirkulasyon-pompalari',
            ],
            'name_keywords' => ['sirkülasyon', 'sirkulasyon', 'inline', 'rekorlu'],
            'exclude_keywords' => ['hidrofor', 'dalgıç', 'jet', 'drenaj'],
        ],
    ],

    'limits' => [
        'max_recommendations' => 8,
        'flow_tolerance_low' => 0.85,
        'head_tolerance_low' => 0.90,
        'oversize_penalty_ratio' => 2.5,
    ],

];
