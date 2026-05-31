<?php

return [
    'name' => env('KOSAR_NAME', 'Koşar'),
    'legal_name' => env('KOSAR_LEGAL_NAME', 'Kosar Havalandırma ve Sulama Sistemi'),
    'description' => env(
        'KOSAR_DESCRIPTION',
        'Dalgıç pompa, hidrofor, fan ve petek temizleme makineleri. Türkiye\'nin pompa ve havalandırma e-ticaret platformu. 1000 TL üzeri ücretsiz kargo.',
    ),
    'url' => env('APP_URL', 'http://127.0.0.1:8001'),
    'free_shipping_min' => (float) env('KOSAR_FREE_SHIPPING', 1000),
    'contact' => [
        'phone' => env('KOSAR_PHONE', '+90 224 443 00 00'),
        'email' => env('KOSAR_EMAIL', 'info@kosar.com.tr'),
        'whatsapp' => env('KOSAR_WHATSAPP', '905554443000'),
        'address' => env('KOSAR_ADDRESS', 'Nilüfer, Bursa'),
    ],
    'admin_password' => env('ADMIN_PASSWORD', 'kosar-dev'),
    'order_prefix' => env('KOSAR_ORDER_PREFIX', 'KOS'),

    'default_locale' => env('KOSAR_DEFAULT_LOCALE', 'tr'),
    'locales' => ['tr'],

    'defaults' => [
        'hero_badge' => 'Havalandırma ve Sulama E-Ticaret',
        'hero_title' => 'Su ve Hava Sistemlerinde Güvenilir Çözüm Ortağınız',
        'hero_subtitle' => null,
        'promo_text' => '1000 TL üzeri kargo bedava · 14 gün kolay iade',
    ],

    /*
    | Ana sayfa banner varsayılan ölçüsü (panelden değiştirilebilir).
    | Panel → Ana sayfa banner → Genişlik / Yükseklik alanları.
    */
    'home_banner' => [
        'width' => 1440,
        'height' => 520,
        'max_kb' => 2048,
        'formats' => 'JPG, PNG veya WebP',
    ],

    /*
    | Panelde her görsel alanında gösterilen önerilen ölçüler (px).
    | home_banner_slider genişliği/yüksekliği panelden (HomeBannerSpec) okunur.
    */
    'image_specs' => [
        'home_banner_tile' => [
            'width' => 800,
            'height' => 600,
            'hint' => 'Ana sayfa kutu alanı: ürün, kategori veya kampanya kutusu (4:3).',
            'formats' => 'JPG, PNG veya WebP',
            'max_kb' => 2048,
            'safe_zone' => 'Kareye yakın kırpma olur; önemli metin görselin ortasında olsun.',
        ],
        'category' => [
            'width' => 960,
            'height' => 540,
            'hint' => 'Kategori kartları ve liste görselleri (16:9).',
            'formats' => 'JPG, PNG veya WebP',
            'max_kb' => 2048,
        ],
        'brand_logo' => [
            'width' => 400,
            'height' => 160,
            'hint' => 'Marka şeridi ve liste; şeffaf PNG tercih edilir.',
            'formats' => 'PNG, SVG, JPG veya WebP',
            'max_kb' => 2048,
            'safe_zone' => 'Logo etrafında boşluk bırakın; arka plan şeffaf olabilir.',
        ],
        'product_cover' => [
            'width' => 1200,
            'height' => 1200,
            'hint' => 'Ürün kartı ve ürün sayfası ana görsel (kare).',
            'formats' => 'JPG, PNG veya WebP',
            'max_kb' => 5120,
        ],
        'product_gallery' => [
            'width' => 1200,
            'height' => 1200,
            'hint' => 'Ürün galerisi küçük görselleri (kare, aynı ölçü).',
            'formats' => 'JPG, PNG veya WebP',
            'max_kb' => 5120,
        ],
        'site_logo' => [
            'width' => 320,
            'height' => 80,
            'hint' => 'Header ve footer logosu; yatay format.',
            'formats' => 'PNG (şeffaf), SVG, WebP',
            'max_kb' => 2048,
            'safe_zone' => 'Şeffaf PNG export önerilir.',
        ],
        'site_favicon' => [
            'width' => 512,
            'height' => 512,
            'hint' => 'Sekme ikonu (favicon); kare format önerilir.',
            'formats' => 'PNG, ICO, SVG veya WebP',
            'max_kb' => 512,
            'safe_zone' => '32×32 veya 512×512 kare görsel.',
        ],
        'hero_image' => [
            'width' => 1440,
            'height' => 520,
            'hint' => 'Hero sağ panel görseli (site ayarları). Slider ölçüsüyle uyumlu tutun.',
            'formats' => 'JPG, PNG veya WebP',
            'max_kb' => 5120,
        ],
    ],

    /*
    | Footer — ödeme kartları ve Türkiye uyumluluk rozetleri (panelden açılıp kapatılır).
    */
    'footer' => [
        'default_cards' => ['visa', 'mastercard', 'paypal', 'amex', 'visa_electron', 'maestro'],
        'card_image_hint' => 'Varsayılan kartlar vektör ikondur. Özel kart için ~64×40 px PNG yükleyin.',
        'default_compliance' => ['3d_secure', 'ssl', 'secure_checkout', 'etbis'],
        'cards' => [
            'visa' => ['label' => 'Visa', 'brand' => 'visa'],
            'mastercard' => ['label' => 'Mastercard', 'brand' => 'mastercard'],
            'paypal' => ['label' => 'PayPal', 'brand' => 'paypal'],
            'amex' => ['label' => 'American Express', 'brand' => 'amex'],
            'visa_electron' => ['label' => 'Visa Electron', 'brand' => 'visa_electron'],
            'maestro' => ['label' => 'Maestro', 'brand' => 'maestro'],
            'troy' => ['label' => 'Troy', 'brand' => 'troy'],
        ],
        'compliance' => [
            '3d_secure' => ['label' => '3D Secure', 'icon' => 'shield', 'hint' => 'Kartlı ödemelerde ek doğrulama'],
            'ssl' => ['label' => '256-bit SSL', 'icon' => 'lock', 'hint' => 'Şifreli bağlantı'],
            'secure_checkout' => ['label' => 'Güvenli Ödeme', 'icon' => 'shield', 'hint' => 'PCI-DSS uyumlu altyapı'],
            'etbis' => ['label' => 'ETBİS', 'icon' => null, 'hint' => 'Elektronik Ticaret Bilgi Sistemi kaydı', 'special' => 'etbis'],
            'kvkk' => ['label' => 'KVKK', 'icon' => 'shield', 'hint' => 'Kişisel verilerin korunması', 'special' => 'kvkk'],
        ],
        'payment_icons' => [
            'kredi_karti' => 'credit-card',
            'havale' => 'arrow-path',
            'kapida_odeme' => 'truck',
        ],
    ],
];
