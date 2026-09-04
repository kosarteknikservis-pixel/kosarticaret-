<?php

/**
 * Blog gövde metnine kontekstüel kategori linki enjekte kuralları.
 * seo:inject-blog-category-links komutu tarafından kullanılır.
 */
return [
    'max_links_per_post' => 3,

    /*
     | Daha spesifik yaprak kurallar generic hub kurallarından önce gelmeli.
     | İlk eşleşen URL yazıya enjekte edilir; aynı URL tekrar eklenmez.
     */
    'rules' => [
        [
            'patterns' => ['ev tipi hidrofor', 'ev tipi hidroforlar', 'müstakil ev hidrofor'],
            'url' => '/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar',
        ],
        [
            'patterns' => ['hidrofor grubu', 'hidrofor grupları', 'grup hidrofor'],
            'url' => '/kategoriler/hidrofor-sistemleri/hidrofor-grubu',
        ],
        [
            'patterns' => ['hidromat'],
            'url' => '/kategoriler/hidrofor-sistemleri/hidromat',
        ],
        [
            'patterns' => ['derin kuyu pompa', 'derin kuyu dalgıç', 'sondaj pompası', 'derin kuyu dalgic'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa',
        ],
        [
            'patterns' => ['drenaj pompası', 'drenaj pompa', 'drenaj pompasi', 'drenaj dalgıç'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
        ],
        [
            'patterns' => ['foseptik pompa', 'foseptik pompası', 'foseptik pompasi', 'foseptik dalgıç'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa',
        ],
        [
            'patterns' => ['jet pompa', 'jet pompalar', 'jet pompası'],
            'url' => '/kategoriler/su-pompalari/jet-pompalar-derinden-emisli',
        ],
        [
            'patterns' => ['dalgıç pompa', 'dalgic pompa', 'dalgıç pompası', 'dalgic pompasi', 'dalgıç pompalar'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar',
        ],
        [
            'patterns' => ['yangın pompası', 'yangin pompasi', 'yangın pompa', 'yangın pompaları'],
            'url' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari',
        ],
        [
            'patterns' => ['havuz pompası', 'havuz pompa', 'havuz pompasi'],
            'url' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi',
        ],
        [
            'patterns' => ['jakuzi pompası', 'jakuzi pompa', 'jakuzi pompasi'],
            'url' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/jakuzi-pompasi',
        ],
        [
            'patterns' => ['santrifüj pompa', 'santrifuj pompa', 'santrifüj pompası'],
            'url' => '/kategoriler/su-pompalari/santrifuj-pompalar',
        ],
        [
            'patterns' => ['sirkülasyon pompası', 'sirkulasyon pompasi', 'sirkülasyon pompasi'],
            'url' => '/kategoriler/su-pompalari/sirkulasyon-pompalari',
        ],
        [
            'patterns' => ['kademeli pompa', 'kademeli pompası'],
            'url' => '/kategoriler/su-pompalari/kademeli-pompalar',
        ],
        [
            'patterns' => ['sanayi tipi vantilatör', 'sanayi tipi vantilator', 'endüstriyel vantilatör'],
            'url' => '/kategoriler/vantilatorler/sanayi-tipi-vantilator',
        ],
        [
            'patterns' => ['vantilatör', 'vantilator', 'havalandırma fanı'],
            'url' => '/kategoriler/vantilatorler',
        ],
        [
            'patterns' => ['hidrofor', 'hidrofor sistemi', 'hidrofor sistemleri'],
            'url' => '/kategoriler/hidrofor-sistemleri',
        ],
        [
            'patterns' => ['su pompası', 'su pompa', 'su pompasi'],
            'url' => '/kategoriler/su-pompalari',
        ],
    ],
];
