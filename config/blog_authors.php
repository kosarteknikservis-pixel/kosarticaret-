<?php

/**
 * Blog yazar profilleri — E-E-A-T Person şeması ve /yazar/{slug} sayfaları.
 * BlogPost'ta author_slug yoksa default yazar kullanılır.
 */
return [
    'default' => 'kosar-teknik-ekibi',

    'authors' => [
        'kosar-teknik-ekibi' => [
            'name' => 'Koşar Teknik Ekibi',
            'title' => 'Pompa, Hidrofor ve Havalandırma Uzmanları',
            'bio' => 'Koşar Ticaret teknik ekibi; dalgıç pompa, hidrofor, santrifüj pompa ve endüstriyel havalandırma alanlarında saha deneyimi ve ürün bilgisiyle blog rehberlerini hazırlar. Seçim, montaj ve bakım konularında uygulanabilir, satın alma odaklı içerik üretir.',
            'expertise' => [
                'Hidrofor ve basınçlandırma sistemleri',
                'Dalgıç ve santrifüj pompalar',
                'Endüstriyel vantilatör ve havalandırma',
                'Su pompası seçimi ve kapasite hesabı',
            ],
            'linkedin' => null,
        ],
    ],
];
