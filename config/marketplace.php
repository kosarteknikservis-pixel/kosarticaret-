<?php

return [
    'default_vat_rate' => (float) env('KOSAR_VAT_RATE', 20),

    'readiness' => [
        'min_description_length' => 50,
    ],

    'channels' => [
        'trendyol' => [
            'label' => 'Trendyol',
            'type' => 'marketplace',
            'provider' => \App\Services\Marketplace\Providers\TrendyolProvider::class,
        ],
        'hepsiburada' => [
            'label' => 'Hepsiburada',
            'type' => 'marketplace',
            'provider' => \App\Services\Marketplace\Providers\HepsiburadaProvider::class,
        ],
        'n11' => [
            'label' => 'N11',
            'type' => 'marketplace',
            'provider' => \App\Services\Marketplace\Providers\N11Provider::class,
        ],
        'idefix' => [
            'label' => 'Idefix',
            'type' => 'marketplace',
            'provider' => \App\Services\Marketplace\Providers\IdefixProvider::class,
        ],
        'pazarama' => [
            'label' => 'Pazarama',
            'type' => 'marketplace',
            'provider' => \App\Services\Marketplace\Providers\PazaramaProvider::class,
        ],
        'akakce' => [
            'label' => 'Akakçe',
            'type' => 'feed',
            'provider' => \App\Services\Marketplace\Providers\AkakceFeedProvider::class,
        ],
    ],

    'listing_statuses' => [
        'draft' => 'Taslak',
        'pending' => 'Onay bekliyor',
        'published' => 'Yayında',
        'rejected' => 'Reddedildi',
        'delisted' => 'Yayından kaldırıldı',
        'error' => 'Hata',
    ],

    'trendyol' => [
        'base_url' => env('TRENDYOL_API_BASE_URL', 'https://api.trendyol.com'),
        'integration_prefix' => '/integration',
        'order_import_page_size' => 50,
        'order_import_statuses' => ['Created', 'Picking', 'Invoiced', 'Shipped'],
    ],

    'sales_channels' => [
        'website' => 'Web sitesi',
        'trendyol' => 'Trendyol',
        'hepsiburada' => 'Hepsiburada',
        'n11' => 'N11',
        'idefix' => 'Idefix',
        'pazarama' => 'Pazarama',
    ],
];
