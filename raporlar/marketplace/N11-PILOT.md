# N11 pilot

Kaynak: [N11 Ürün Yükleme](https://developer.n11.com/documentation/n11-marketplace-entegrasyonu/urun-yukleme/)

Kurallar uygulanan:
- stok `quantity` >= 1 (0 yasak)
- `listPrice` >= `salePrice`
- HTTPS görsel (jsDelivr seed), zorunlu kategori attribute
- `shipmentTemplate` = TESLIMAT2
- Koşar marka gönderilmez
- Başlık = ürün adı
- Dahili 200–299 EAN barkod gönderilmez (katalog eşleşme riski)

## Kuyruk (HB sırası)

| SKU | Slug | Fiyat | Durum | Not |
|-----|------|-------|-------|-----|
| HD500 | sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt | 3999 | gonderildi | barkodsuz yeniden; task 3499267750 Active |
| WNP400AW | winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v | 5599 | gonderiliyor | sıradaki |
