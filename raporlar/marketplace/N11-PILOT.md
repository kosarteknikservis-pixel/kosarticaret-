# N11 kuyruk

Kaynak: [N11 Ürün Yükleme](https://developer.n11.com/documentation/n11-marketplace-entegrasyonu/urun-yukleme/)  
Sıra = HB kuyruk. Komut: `php artisan marketplace:n11-publish-queue --sleep=15`

Kurallar:
- stok `quantity` >= 1 (0 yasak)
- `listPrice` >= `salePrice`
- HTTPS görsel (jsDelivr seed), zorunlu kategori attribute
- `shipmentTemplate` = TESLIMAT2
- Koşar marka gönderilmez
- Başlık = ürün adı
- Dahili 200–299 EAN barkod gönderilmez (katalog eşleşme riski)
- Hatada dur; listing `pending` olanlar tekrarlanmaz

## Durum

| SKU | Slug | Fiyat | Durum | Not |
|-----|------|-------|-------|-----|
| HMJH15 | troy-hmj-h-15-hidrofor-4-kat-4-daire-24-litre-kure-tankli | 10990 | bekliyor | |
| SDF151 | sumak-sdf-151-temiz-su-dalgic-pompa-monofaze-220v-15-hp | 23599 | bekliyor | |
| HWD1100S | kaysu-hwd-1100s-paslanmaz-govdeli-kirli-su-dalgic-pompasi-15-hp-220-volt | 14799 | bekliyor | |
| SMJH-150 | sumak-smj-150-hidrofor-4-kat-6-daire-24-litre-tankli-hidrofor | 17999 | bekliyor | |
| HMC145 | kaysu-pompa-hmc145-65h-paket-hidrofor-8-kat-18-daire-100-litre-tankli | 21999 | bekliyor | |
| 2HCP160 | kaysu-2hcp-160-cift-kademeli-hidrofor-7-kat-14-daire-50-litre-tankli | 18199 | bekliyor | |
| SMJ220 | sumak-smj-220-jet-paket-hidrofor-8-kat-16-daire-50-litre-tankli | 34199 | bekliyor | |
| HMJ15JET | troy-hmj-15-dokum-govdeli-jet-pompa-1-hp-49-mss-4-m3-h | 8599 | bekliyor | |
| SMJK100 | sumak-smjk-100-kendinden-emisli-jet-pompa-monofaze-220v-1hp | 13599 | bekliyor | |
| SMAC600 | sumak-smac-600-wc-3-unite-foseptik-tahliye-cihazi | 31599 | bekliyor | |
| BC1550N | pedrollo-bc-1550n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa | 41799 | bekliyor | |
| BCM1050N | pedrollo-bcm-1050n-flatorlu-paslanmaz-govdeli-foseptik-dalgic-pompa | 35999 | bekliyor | |
| TOPMULTIAD | pedrollo-top-multi-ad-drenaj-dalgic-pompa-mazot-pompasi-27-mss-4-2-m3h | 23199 | bekliyor | |
| SP100050 | kaysu-sp100050-kapali-fanli-plastik-drenaj-dalgic-su-pompasi | 6999 | bekliyor | |
| SDF152 | sumak-sdf152-komple-paslanmaz-foseptik-dalgic-drenaj-pompa-220-volt-15-hp | 26999 | bekliyor | |
| TS1212H | troy-ts-12-12-h-12v-karavan-hidroforu | 7599 | bekliyor | |
| TS128H | troy-ts-12-8-h-12v-karavan-hidroforu | 7999 | bekliyor | |
| STN2000G | sumak-stn2000g-sintine-dalgic-pompa-6-mss-4500-litre-h-12-volt-manuel | 5999 | bekliyor | |
| HD500 | sumak-hd-500-drenaj-dalgic-pompa-acik-carkli-monofaze-220v-370-watt | 3999 | gonderildi | task 3499267750 |
| WNP400AW | winpo-wnp-400-aw-flatorlu-drenaj-dalgic-pompa-6mss-7m3h-monofoze220v | 5599 | gonderildi | task 3499328042 |
| 4SDF4 | sumak-4sdf4-07-hp-220v-keson-dalgici-1-cikisli | 14199 | bekliyor | |
| WNP400AWGF | winpo-wnp-400-aw-gf-gizli-flatorlu-drenaj-dalgic-pompa-7mss-7m3h-monofaze-220v | 5499 | bekliyor | |
| SDF500GF | sumak-sdf500-gf-sifirdan-emisli-gizli-samandirali-drenaj-pompasi-05-hp | 12399 | bekliyor | |
| SDF6A | sumak-sdf-6a-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp | 15199 | bekliyor | |
| SDF6 | sumak-sdf-6-drenaj-dalgic-pompa-paslanmaz-govdeli-monofaze-220-v-05-hp | 11899 | bekliyor | |
| HQBM60 | kaysu-hqbm60-preferikal-surtme-fanli-su-pompasi | 3699 | bekliyor | |
| SM10 | sumak-sm-10-preferikal-pompa-monofaze-220v-1hp | 13922 | bekliyor | |
| HD1300-2 | sumak-hd-1300-2-flatorlu-kirli-su-foseptik-dalgic-pompa-1-hp | 11199 | bekliyor | |
| HD1700-2 | sumak-hd-1700-2-flatorlu-kirli-su-foseptik-dalgic-pompa-15-hp | 12599 | bekliyor | |
| SBRM18-2-P | sumak-sbrm-182p-parcalayici-bicakli-foseptik-dalgic-pompa-monofaze-220v-18-hp | 33399 | bekliyor | |
| SDF102 | sumak-sdf-102-paslanmaz-govdeli-foseptik-dalgic-pompa-monofaze-220-v-1-hp | 18970 | bekliyor | |
| HKJMH | kaysu-pompa-hidrofor-050-hp-1-kat-1-daire | 5625 | bekliyor | |
| HKJM | kaysu-pompa-hkjmh-10h-jet-hidrofor-4-kat-4-daire-24l-tankli | 10125 | bekliyor | |
| SMJH-150-24 | sumak-smj-150-jet-paket-hidrofor-5-kat-10-daire-24-litre-tankli | 25313 | bekliyor | |
