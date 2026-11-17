# Canlı PSI dağıtım — 17 Kasım 2026

**Canlı:** https://kosarticaret.com/  
**Yöntem:** SSH (`kosar_deploy` → `admin@kosarticaret.com`) seçici dosya  
**DB / .env:** dokunulmadı  

## Yedek

`/home/admin/domains/kosarticaret.com/backups/pre-psi-20261117-144609`

- `psi-code.tgz`, `psi-banners.tgz`, `database.sqlite`, `ROLLBACK.md`

## Yüklenen

- `ImageVariant` + `HomeBanner` srcset (`banner` / `banner-sm`)
- Home / banner slide / layout / meta-pixel Blade
- `shop.css` / `shop.min.css` / `shop.min.js`
- Urbanist self-host fonts
- Optimize hero WebP + `_optimized` varyantlar
- `public/.htaccess`, `public/storage/.htaccess`

## Canlı doğrulama (HTTP)

| Kontrol | Sonuç |
|---|---|
| Google Fonts stylesheet | Yok |
| Urbanist self-host preload | Var |
| Preload + imagesrcset optimize banner | Var |
| `banner-sm` 200 / 40190 byte | OK |
| `kosarLoadGtm` / `kosarLoadMetaPixel` | Var (gecikmeli) |
| Head anlık gtm.start | Yok |
| Cache-Control storage/css | Hâlâ `max-age=604800` (Nginx; ayrıca `NGINX-CACHE.md`) |

## Geri alma

```bash
cd /home/admin/domains/kosarticaret.com
tar -xzf backups/pre-psi-20261117-144609/psi-code.tgz
tar -xzf backups/pre-psi-20261117-144609/psi-banners.tgz
cd kosar && php artisan optimize:clear && php artisan view:cache
```
