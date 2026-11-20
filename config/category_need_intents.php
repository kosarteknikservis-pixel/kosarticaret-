<?php

/**
 * Kategori sayfasında ihtiyaç yönlendirme şeridi.
 * Yalnızca mevcut storefront URL’leri; yeni slug üretilmez.
 *
 * exclude_slugs: şeritte geçen kategori slug’larını kardeş/hub şeritlerinden düşürmek için.
 */
return [
    'hidrofor-sistemleri/hidroforlar' => [
        'label' => 'İhtiyacınıza göre',
        'items' => [
            [
                'label' => 'Ev tipi hidroforlar',
                'href' => '/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar',
            ],
            [
                'label' => 'Çok pompalı hidrofor grupları',
                'href' => '/kategoriler/hidrofor-sistemleri/hidrofor-grubu',
            ],
            [
                'label' => 'Apartman için seçim rehberi',
                'href' => '/blog/kac-katli-binaya-hangi-hidrofor',
            ],
        ],
        'exclude_slugs' => [
            'ev-tipi-hidroforlar',
            'hidrofor-grubu',
        ],
    ],
];
