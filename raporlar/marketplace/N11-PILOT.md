# N11 kuyruk

Kaynak: [N11 Ürün Yükleme](https://developer.n11.com/documentation/n11-marketplace-entegrasyonu/urun-yukleme/)  
Komut: `php artisan marketplace:n11-publish-queue --sleep=15`  
2026-10-29 deploy: **gönderilen=32 atlanan=2** (HD500 + WNP400AW zaten vardı). Toplam 34/34.

Kurallar:
- stok `quantity` >= 1 (0 yasak)
- `listPrice` >= `salePrice`
- HTTPS görsel (jsDelivr seed), zorunlu kategori attribute
- `shipmentTemplate` = TESLIMAT2
- Koşar marka gönderilmez
- Başlık = ürün adı
- Dahili 200–299 EAN barkod gönderilmez
- Hatada dur; listing `pending` olanlar tekrarlanmaz

## Durum

| SKU | Fiyat | Durum |
|-----|-------|-------|
| HMJH15 | 10990 | gonderildi |
| SDF151 | 23599 | gonderildi |
| HWD1100S | 14799 | gonderildi |
| SMJH-150 | 17999 | gonderildi |
| HMC145 | 21999 | gonderildi |
| 2HCP160 | 18199 | gonderildi |
| SMJ220 | 34199 | gonderildi |
| HMJ15JET | 8599 | gonderildi |
| SMJK100 | 13599 | gonderildi |
| SMAC600 | 31599 | gonderildi |
| BC1550N | 41799 | gonderildi |
| BCM1050N | 35999 | gonderildi |
| TOPMULTIAD | 23199 | gonderildi |
| SP100050 | 6999 | gonderildi |
| SDF152 | 26999 | gonderildi |
| TS1212H | 7599 | gonderildi |
| TS128H | 7999 | gonderildi |
| STN2000G | 5999 | gonderildi |
| HD500 | 3999 | gonderildi |
| WNP400AW | 5599 | gonderildi |
| 4SDF4 | 14199 | gonderildi |
| WNP400AWGF | 5499 | gonderildi |
| SDF500GF | 12399 | gonderildi |
| SDF6A | 15199 | gonderildi |
| SDF6 | 11899 | gonderildi |
| HQBM60 | 3699 | gonderildi |
| SM10 | 13922 | gonderildi |
| HD1300-2 | 11199 | gonderildi |
| HD1700-2 | 12599 | gonderildi |
| SBRM18-2-P | 33399 | gonderildi |
| SDF102 | 18970 | gonderildi |
| HKJMH | 5625 | gonderildi |
| HKJM | 10125 | gonderildi |
| SMJH-150-24 | 25313 | gonderildi |
