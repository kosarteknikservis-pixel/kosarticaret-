# Aşama A v1 — Yayın sonrası kontrol planı

Canlıya gönderildikten sonra, sırayla:

## 1) 14 bağlantı düzeltmesi (kaynak HTML)

Her kaynakta eski `from` **yok**, beklenen `to` (veya unwrap metni) **var**:

| Finding | Kaynak slug | Kontrol |
|---:|---|---|
| 1 | `kademeli-pompa-kisa-cevrim-start-stop`, `jet-pompa-kisa-cevrim-start-stop` | `hidrofor-start-stop-salinimi-kisa-cevrim-teshis` yok → `…-kisa-cevrim` var |
| 2–5 | `santrifuj-pompa-kisa-cevrim-start-stop` (+ ilgili) | tank/presostat/debi eski path yok |
| 6–8 | `kosar-vantilator-modelleri-rehberi` | marka karşılaştırma → seçim rehberi; iş güvenliği linki yok, metin var |
| 7 | `salyangoz-pompa-rehberi` | `…-secimi` yok → `…-secimi-rehberi` |
| 9–11 | kademeli yangın kaynakları | bakim/by-pass unwrap; tasarım → `yangin-pompasi-nedir-nasil-secilir` |
| 12 | `kademeli-pompa-debi-dususu-teshis` | `…-pompalar` yok → `monoblok-yatay-kademeli` |
| 14–15 | `troy-hmj-h-15-…`, `troy-hmj-15-…` | `/marka/sumak`; ürün slug `troy-hmj-h-15-…` |

Komut (yerel/CI): `py -3 docs/audit/release/asama-a-v1/live-precheck.py` — `ready_ops` 0, `already_applied_ops` ≈ 24 olmalı.

## 2) Eski karşılıksız URL → 404

| Path | Beklenen |
|---|---|
| `/kategori/yedek-parca` | 404 |
| `/urun-kategori/yedek-parca` | 404 |
| `/kategoriler/yedek-parca-ve-aksesuarlar` | 404 |
| `/kategori/genlesme-tanklari` | 404 |
| `/urun-kategori/bahce-yapi-market/genlesme-tanklari` | 404 |
| `/urun/alarko-wsd25-17-kirli-ve-atik-su-dalgic-1-5-hp` | 404 |

Meta refresh yok; hata metni + `/ara` + `/iletisim` çalışır.

## 3) `sirala` → `siralama`

| İstek | Beklenen |
|---|---|
| `…/su-pompalari?sirala=fiyat-artan` | 301 → `?siralama=fiyat-artan` |
| `?sirala=uydurma&page=2` | 301 → `?page=2` |
| `?sirala=isim&siralama=fiyat-azalan&marka=sumak` | 301; `siralama` + `marka` korunur |
| Temiz kategori | 200, robots index, canonical temiz |
| `?siralama=fiyat-artan` | 200, robots noindex,follow, canonical temiz |

## 4) Regresyon (bozulmama)

| URL | Beklenen |
|---|---|
| `/kategori/dalgic-pompalar` | 301 → nested dalgıç |
| `/kategoriler/su-pompalari` | 200 |
| `/kategoriler/hidrofor-sistemleri` | 200 |
| `/kategoriler/…/kirli-su-dalgic-pompa` | 200 |
| `/marka/sumak` | 200 |
| Aktif PDP örneği | 200 |
| `/ara?q=pompa` | 200 |
| `/iletisim` | 200 |
