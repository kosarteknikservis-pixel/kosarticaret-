<?php

/**
 * Eski WordPress / WooCommerce URL yönlendirmeleri (301).
 */
return [
    'exact' => [
        '/siparisler' => '/hesabim',
        '/favori-listesi' => '/favoriler',
        '/magaza' => '/urunler',
        '/locations.kml' => '/',
        '/hakkimizda' => '/sayfa/hakkimizda',

        // GSC 5xx → slug değişimi veya kaldırılan ürünler (Search Console 2026-06-06)
        '/urun/kosar-ksv-750-sanayi-tipi-vantilator' => '/urun/kosar-ksv-750-sanayi-tipi-vantilator-30-ayakli',
        '/urun/kaysu-pompa-hidrofor-0-50-hp-1-kat-1-daire' => '/urun/kaysu-pompa-hidrofor-050-hp-1-kat-1-daire',
        '/urun/sumak-smjkt100-trifaze-jet-pompa' => '/urun/sumak-smjkt-100-kendinden-emisli-jet-pompa-trifaze-380v-1hp',
        '/urun/sumak-smj-150-jet-hidrofor-4-kat-8-daire-24-litre-tankli' => '/urun/sumak-smj-150-hidrofor-4-kat-6-daire-24-litre-tankli-hidrofor',
        '/urun/sumak-sdf15y-drenaj-dalgic-pompasi' => '/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
        '/urun/sumak-smh200-havuz-pompasi' => '/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi',
        '/urun/winpo-wnp-cmi-8-40-t-full-paslanmaz-cok-kademeli-santrifuj-pompa' => '/kategoriler/su-pompalari/kademeli-pompalar',
        '/urun/elektrikli-soba-isitici-1600-w' => '/urunler',
        '/urun/etna-ear4-65-32-75-yatay-monoblok-tek-kademeli-pompa-ear-serisi' => '/marka/etna',
    ],

    'patterns' => [],

    'brand_aliases' => [
        'sumak-bicakli-foseptik-dalgic-pompa' => 'sumak',
        'sumak-pompa' => 'sumak',
        'sumak-jet-su-pompasi' => 'sumak',
        'sumak-santrifuj-pompa' => 'sumak',
        'pedrollo-jet-su-pompa' => 'pedrollo',
        'kaysu-pompa' => 'kaysu',
        'kaysu-preferikal-surtme-fanli-pompa' => 'kaysu',
        'winpo-jet-su-pompa' => 'winpo',
        'winpo-tek-pompali-paket-hidrofor' => 'winpo',
        'winpo-yatik-tankli-hidrofor' => 'winpo',
        'etna-tek-pompali-paket-hidrofor' => 'etna',
        'etna-uc-pompali-paket-hidrofor' => 'etna',
        'sumak-santrifuj-pompa' => 'sumak',
        'sumak-keson-kuyu-dalgic-pompa' => 'sumak',
    ],

    'blog_posts' => [
        '/hidrofor-nedir-ne-ise-yarar' => '/blog/hidrofor-nedir-ne-ise-yarar-nasil-calisir',
        '/dalgic-pompa-nedir' => '/blog/dalgic-pompa-bakimi',
        '/sirkulasyon-pompasi-nedir' => '/kategoriler/su-pompalari/sirkulasyon-pompalari',
        '/su-basinc-sistemi-pompa-mi-hidrofor-mu' => '/kategoriler/hidrofor-sistemleri',
        '/sanayi-tipi-vantilator-rehberi' => '/kategoriler/vantilatorler/sanayi-tipi-vantilator',
    ],

    'category_aliases' => [
        'dalgic-pompalar' => 'su-pompalari/dalgic-pompalar',
        'yedek-parca' => 'yedek-parca-ve-aksesuarlar',
    ],

    /**
     * /urun-kategori/{key} → /kategoriler/{value}
     * Sayfalama (/page/N) otomatik temizlenir.
     */
    'category_paths' => [
        'bahce-yapi-market' => 'su-pompalari',
        'bahce-yapi-market/dalgic-pompa' => 'su-pompalari/dalgic-pompalar',
        'bahce-yapi-market/dalgic-pompa/bicakli-foseptik-dalgic-pompalar' => 'su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa',
        'bahce-yapi-market/dalgic-pompa/drenaj-dalgic-pompalari' => 'su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
        'bahce-yapi-market/dalgic-pompa/keson-kuyu-dalgic-pompalari' => 'su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa',
        'bahce-yapi-market/dalgic-pompa/temiz-su-dalgic-pompalari' => 'su-pompalari/dalgic-pompalar/temiz-su-dalgic-pompasi',
        'bahce-yapi-market/hidroforlar' => 'hidrofor-sistemleri/hidroforlar',
        'bahce-yapi-market/hidroforlar/frekans-kontrollu-hidrofor' => 'hidrofor-sistemleri/frekans-kontrollu-hidroforlar',
        'bahce-yapi-market/su-pompasi/havuz-pompasi' => 'su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi',
        'bahce-yapi-market/su-pompalari/havuz-pompasi' => 'su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi',
        'bahce-yapi-market/su-pompasi/paslanmaz-govdeli-su-pompalari' => 'su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal',
        'bahce-yapi-market/su-pompasi/santrifuj-pompalar' => 'su-pompalari/santrifuj-pompalar',
        'dalgic-pompa' => 'su-pompalari/dalgic-pompalar',
        'dalgic-pompa/drenaj-dalgic-pompasi' => 'su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa',
        'su-pompasi/yatay-kademeli-pompalar' => 'su-pompalari/kademeli-pompalar/yatay-kademeli-pompalar',
        'genel' => '/urunler',
        'hidrofor-aksesuarlari/genlesme-tanki' => 'hidrofor-sistemleri',
        'hidroforlar/ev-tipi-hidrofor' => 'hidrofor-sistemleri/ev-tipi-hidroforlar',
        'sirkulasyon-pompasi' => 'su-pompalari/sirkulasyon-pompalari',
        'su-pompalari/santrifuj-pompalar' => 'su-pompalari/santrifuj-pompalar',
        'su-pompalari/santrifuj-pompalar/salyangoz-pompalar-bol-su-veren' => 'su-pompalari/santrifuj-pompalar/salyangoz-pompalar-bol-su-veren',
        'su-pompalari/santrifuj-pompalar/santrifuj-pompalar-sulama' => 'su-pompalari/santrifuj-pompalar',
        'su-pompasi/santrifuj-pompalar' => 'su-pompalari/santrifuj-pompalar',
        'anahtar-grubu' => 'elektrik-ve-aydinlatma',
        'termostat-ve-sinyal-lamba-grubu' => 'elektrik-ve-aydinlatma',
        'elektronik-grubu' => 'elektrik-ve-aydinlatma',
        'musluk-ve-conta-grubu' => 'elektrik-ve-aydinlatma',
        'endustriyel-urunler/rezistans-ve-aksesuarlar/musluk-ve-conta-grubu' => 'elektrik-ve-aydinlatma',
        'endustriyel-urunler/rezistans-ve-aksesuarlar/elektronik-grubu' => 'elektrik-ve-aydinlatma',
        'bahce-yapi-market-su-pompalari-paslanmaz-govdeli-su-pompalari' => 'su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal',
    ],
];
