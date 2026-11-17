# Canlı Nginx / CloudPanel cache notu (PSI “verimli önbellek”)

Canlıda (17 Kas 2026) gözlenen:

- `/storage/banners/*.webp` → `Cache-Control: public, max-age=604800` (7 gün)
- `/css/shop.min.css` → `max-age=604800`
- `/js/shop.min.js` → Cache-Control boş

Yerel `public/.htaccess` ve `public/storage/.htaccess` 1 yıl tanımlar; Nginx bu dosyaları okumaz.

## Önerilen location (örnek)

```nginx
location ~* \.(?:css|js|woff2|webp|avif|jpe?g|png|svg|ico)$ {
    expires 1y;
    add_header Cache-Control "public, max-age=31536000, immutable";
    access_log off;
    try_files $uri =404;
}
```

`?v=` / hash’li Vite asset’leri ile immutable güvenlidir. Deploy sonrası response header doğrulanmalı.
