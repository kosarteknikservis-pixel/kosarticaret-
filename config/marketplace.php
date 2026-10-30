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
        'pttavm' => [
            'label' => 'PTT AVM',
            'type' => 'marketplace',
            'provider' => \App\Services\Marketplace\Providers\PttAvmProvider::class,
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

    'hepsiburada' => [
        'mpop_base_url' => env('HEPSIBURADA_MPOP_BASE_URL', 'https://mpop.hepsiburada.com'),
        'listing_base_url' => env('HEPSIBURADA_LISTING_BASE_URL', 'https://listing-external.hepsiburada.com'),
        // Canlı leaf kategori; boşsa marketplace:hb-prepare-mappings API ile bulur.
        'default_pump_category_id' => env('HEPSIBURADA_PUMP_CATEGORY_ID'),
        'default_pump_category_name' => env('HEPSIBURADA_PUMP_CATEGORY_NAME', 'Su Motoru'),
        // Site host (fallback). HB crawler çoğu hostingtе WAF ile kesiliyor.
        'public_asset_base_url' => env('HEPSIBURADA_PUBLIC_ASSET_BASE_URL', 'https://kosarticaret.com'),
        // site | jsdelivr | catbox | auto (seed→jsdelivr, yoksa catbox)
        'image_mirror' => env('HEPSIBURADA_IMAGE_MIRROR', 'auto'),
        'github_repo' => env('HEPSIBURADA_GITHUB_REPO', 'kosarteknikservis-pixel/kosarticaret-'),
        'github_branch' => env('HEPSIBURADA_GITHUB_BRANCH', 'main'),
        // HB Ocak 2024: görsel indirme IP'leri — hosting firewall'da allow edilmeli.
        'image_crawler_ips' => [
            '193.28.225.94',
            '185.92.214.94',
            '34.78.190.48',
            '104.155.47.90',
            '34.76.71.175',
            '35.240.98.85',
        ],
    ],

    'n11' => [
        'base_url' => env('N11_API_BASE_URL', 'https://api.n11.com'),
        'integrator' => env('N11_INTEGRATOR', 'KosarTicaret'),
        'preparing_day' => (int) env('N11_PREPARING_DAY', 2),
        'default_shipment_template' => env('N11_SHIPMENT_TEMPLATE', ''),
        'default_pump_category_id' => env('N11_PUMP_CATEGORY_ID'),
        'default_pump_category_name' => env('N11_PUMP_CATEGORY_NAME', 'Su Motoru ve Pompa'),
        'github_repo' => env('N11_GITHUB_REPO', 'kosarteknikservis-pixel/kosarticaret-'),
        'github_branch' => env('N11_GITHUB_BRANCH', 'main'),
    ],

    'idefix' => [
        'base_url' => env('IDEFIX_API_BASE_URL', 'https://merchantapi.idefix.com'),
        'delivery_duration' => (int) env('IDEFIX_DELIVERY_DURATION', 2),
        'default_pump_category_id' => env('IDEFIX_PUMP_CATEGORY_ID'),
        'default_pump_category_name' => env('IDEFIX_PUMP_CATEGORY_NAME', 'Su Motoru'),
        'github_repo' => env('IDEFIX_GITHUB_REPO', 'kosarteknikservis-pixel/kosarticaret-'),
        'github_branch' => env('IDEFIX_GITHUB_BRANCH', 'main'),
    ],

    'pttavm' => [
        'base_url' => env('PTTAVM_API_BASE_URL', 'https://integration-api.pttavm.com'),
        'estimated_courier_delivery' => (int) env('PTTAVM_DELIVERY_DAYS', 2),
        'default_pump_category_id' => env('PTTAVM_PUMP_CATEGORY_ID'),
        'default_pump_category_name' => env('PTTAVM_PUMP_CATEGORY_NAME', 'Su Motoru'),
        'github_repo' => env('PTTAVM_GITHUB_REPO', 'kosarteknikservis-pixel/kosarticaret-'),
        'github_branch' => env('PTTAVM_GITHUB_BRANCH', 'main'),
    ],

    'sales_channels' => [
        'website' => 'Web sitesi',
        'trendyol' => 'Trendyol',
        'hepsiburada' => 'Hepsiburada',
        'n11' => 'N11',
        'idefix' => 'Idefix',
        'pttavm' => 'PTT AVM',
        'pazarama' => 'Pazarama',
    ],
];
