<?php

/**
 * Blog yazar profilleri — E-E-A-T Person şeması ve /yazar/{slug} sayfaları.
 * BlogPost'ta author_slug yoksa default yazar kullanılır.
 */
return [
    'default' => 'kosar-teknik-ekibi',

    'authors' => [
        'kosar-teknik-ekibi' => [
            'name' => 'Koşar Ticaret Teknik Editörü',
            'title' => 'Pompa, Hidrofor ve Endüstriyel Havalandırma',
            'bio' => 'Koşar Ticaret bünyesindeki teknik editör ekibi; dalgıç pompa, hidrofor, santrifüj pompa ve endüstriyel vantilatör konularında saha deneyimi ve ürün bilgisine dayalı rehber içerikleri hazırlar. Seçim, montaj, bakım ve arıza giderme konularında uygulanabilir, satın alma odaklı bilgi sunar.',
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
