# Hidrofor blog kuyruğu

Günde 1 yazı yayınlamak için hazırlanmış JSON dosyaları.

## Günlük komut

```bash
php artisan blog:publish-due --force
```

Önizleme:

```bash
php artisan blog:publish-due --dry-run
```

Tek yazı manuel:

```bash
php artisan blog:import database/blog-queue/01-apartman-hidrofor-secimi.json --force
```

## Takvim

| Gün | Tarih | Dosya | Durum |
|-----|-------|-------|-------|
| 1 | 2026-06-10 | 01-apartman-hidrofor-secimi.json | Hazır |
| 2 | 2026-06-11 | 02-ev-tipi-hidrofor-rehberi.json | Hazır |
| 3 | 2026-06-12 | 03-hidrofor-hidromat-farki.json | Bekliyor |
| 4 | 2026-06-13 | 04-apartmanda-su-basinci-dusuk.json | Bekliyor |
| 5 | 2026-06-14 | 05-hidrofor-surekli-calisyor.json | Bekliyor |
| 6 | 2026-06-15 | 06-hidrofor-basinc-tanki-secimi.json | Bekliyor |
| 7 | 2026-06-16 | 07-pedrollo-sumak-hidrofor-karsilastirma.json | Bekliyor |
| 8 | 2026-06-17 | 08-hidrofor-bakim-rehberi.json | Bekliyor |
| 9 | 2026-06-18 | 09-inverter-hidrofor-avantajlari.json | Bekliyor |

## Kapak görseli

Panelden yüklerken: **960×540 px** (16:9), JPG/PNG/WebP.

## SEO başlık kuralı

Site adını yazmayın; sistem sonuna otomatik `| Koşar` ekler.

## Canlıya taşıma

Workflow deploy sonrası sunucuda:

```bash
php artisan blog:publish-due --force
```

İsteğe bağlı cron (her gün 09:00):

```cron
0 9 * * * cd /path/to/kosar && php artisan blog:publish-due --force
```
