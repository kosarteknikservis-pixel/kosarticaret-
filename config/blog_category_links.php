<?php

/**
 * Blog gövde metnine kontekstüel kategori linki enjekte kuralları.
 * seo:inject-blog-category-links komutu tarafından kullanılır.
 */
return [
    'max_links_per_post' => 3,

    'rules' => [
        [
            'patterns' => ['dalgıç pompa', 'dalgic pompa', 'dalgıç pompası', 'dalgic pompasi'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar',
        ],
        [
            'patterns' => ['hidrofor', 'hidrofor sistemi', 'hidrofor sistemi'],
            'url' => '/kategoriler/hidrofor-sistemleri',
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
            'patterns' => ['drenaj pompası', 'drenaj pompa', 'drenaj pompasi'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
        ],
        [
            'patterns' => ['derin kuyu pompa', 'derin kuyu dalgıç', 'sondaj pompası'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa',
        ],
        [
            'patterns' => ['foseptik pompa', 'foseptik pompası', 'foseptik pompasi'],
            'url' => '/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa',
        ],
        [
            'patterns' => ['yangın pompası', 'yangin pompasi', 'yangın pompa'],
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
            'patterns' => ['sanayi tipi vantilatör', 'sanayi tipi vantilator', 'endüstriyel vantilatör'],
            'url' => '/kategoriler/vantilatorler/sanayi-tipi-vantilator',
        ],
        [
            'patterns' => ['vantilatör', 'vantilator', 'havalandırma fanı'],
            'url' => '/kategoriler/vantilatorler',
        ],
        [
            'patterns' => ['su pompası', 'su pompa', 'su pompasi'],
            'url' => '/kategoriler/su-pompalari',
        ],
    ],
];
