<?php

/**
 * Kategoriye özel varsayılan ürün sırası (SKU listesi).
 * Global products.featured alanına dokunmaz.
 *
 * Davranış (CatalogCategoryPins + CatalogQuery):
 * - Yalnız ilgili kategori show varsayılan sıralamasında uygulanır.
 * - Kullanıcı fiyat/isim sıralaması seçince tamamen devreden çıkar.
 * - Stokta olmayan pin'ler editöryel kümede kalır ama stoktakilerden sonra gelir.
 * - Eşitlikte kararlı sıra: pin sırası, sonra id DESC.
 * - Ana sayfa / arama / blog / diğer kategoriler etkilenmez.
 */

return [
    'hidroforlar' => [
        // Ev tipi / giriş fiyat — marka + kontrol çeşitliliği; aynı model ailesi varyantı yok
        'HMJH-15',           // Sumak 24 L küre
        'WNPKH100',          // Winpo WNP 100 tanklı (WNPH100 hidromat varyantı bilinçli elendi)
        'Kaysu HKJM-1',      // Kaysu 24 L
        'PKMH60-24-LT',      // Pedrollo PKM60 giriş
        '4CPM100CY24',       // Pedrollo 4CPm sessiz paket
        '2CPM25-130N-Y50LT', // Pedrollo 2CPm 50 L — farklı debi bandı
    ],
];
