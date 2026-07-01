# Hidrofor blog kuyruğu

Günde 1–3 yazı yayınlanabilir. Aynı `publish_on` tarihine sahip tüm dosyalar tek `blog:publish-due` çalıştırmasında içe aktarılır.

## İçerik uzunluğu standardı

| Tip | Hedef |
|-----|-------|
| Cluster yazı (03–09) | **900–1200 kelime** — ne çok kısa ne gereksiz uzun |
| Pillar (hidrofor-nedir…) | 1500–2000 kelime |
| FAQ | 3–5 soru, cevap 2–4 cümle |

Her yazıda: giriş, 3–5 H2, en az 6 iç link, Pompa Seçici + iletişim CTA.

## Günlük 3 yazı spam olur mu?

**Hayır** — Google frekansa değil kaliteye bakar. Koşullar:

- Her yazı farklı konu ve farklı birincil anahtar kelime
- Kopyala-yapıştır paragraf yok; tablo/liste yapısı yazıdan yazıya aynı olmamalı
- İç linkler doğal; aynı anchor 5 yazıda tekrarlanmasın
- Kapak görseli + benzersiz meta description

## Komutlar

```bash
php artisan blog:publish-due --force
php artisan blog:publish-due --dry-run
php artisan blog:import database/blog-queue/03-hidrofor-hidromat-farki.json --force
```

## Takvim (günde 3)

| Tarih | Dosyalar |
|-------|----------|
| 2026-06-10 | 01 |
| 2026-06-11 | 02 |
| 2026-06-12 | 03, 04, 05 |
| 2026-06-13 | 06, 07, 08 |
| 2026-06-14 | 09 |

## Kapak görseli

Panelden yüklerken: **960×540 px** (16:9), JPG/PNG/WebP.

## SEO başlık kuralı

Site adını yazmayın; sistem sonuna otomatik `| Koşar` ekler.

## Canlıda cron

```cron
0 9,14 * * * cd /path/to/kosar && php artisan blog:publish-due --force
```

## Otomatik index bildirimi

Blog import ve panelden yayınlanan yazılar **IndexNow** ile otomatik bildirilir (Panel → Site ayarları → Genel → IndexNow). Google Indexing API için `.env` içinde `GOOGLE_INDEXING_ENABLED=true` ve service account JSON yolu gerekir.
