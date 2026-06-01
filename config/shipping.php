<?php

return [
    'free_shipping_min' => (float) env('KOSAR_FREE_SHIPPING', 1000),
    'cod_fee' => 29.90,
    'vat_rate' => 0.20,
    'shipping_rates' => [
        'standart' => 0,
        'hizli' => 149.90,
    ],
    'cities' => [
        'Adana', 'Ankara', 'Antalya', 'Bursa', 'İstanbul', 'İzmir', 'Konya', 'Mersin',
    ],
    'shipping_methods' => [
        ['id' => 'standart', 'name' => 'Standart Kargo', 'desc' => '1000 TL üzeri ücretsiz', 'eta' => '2-4 iş günü', 'fee' => 0, 'active' => true],
        ['id' => 'hizli', 'name' => 'Hızlı Kargo', 'desc' => 'Öncelikli sevkiyat', 'eta' => '1-2 iş günü', 'fee' => 149.90, 'active' => true],
    ],
    'payment_methods' => [
        ['id' => 'kredi_karti', 'name' => 'Kredi / Banka Kartı', 'desc' => 'Güvenli 3D Secure ödeme'],
        ['id' => 'havale', 'name' => 'Havale / EFT', 'desc' => 'Sipariş sonrası IBAN bilgisi'],
        ['id' => 'kapida_odeme', 'name' => 'Kapıda Ödeme', 'desc' => 'Teslimatta (+{fee} TL)'],
    ],
];
