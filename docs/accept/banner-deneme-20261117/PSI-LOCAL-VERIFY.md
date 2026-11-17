# PSI düzeltmeleri — yerel doğrulama (2026-11-17)

## Hero boyutları (önce → sonra)

| Dosya | Orijinal | Optimize orijinal | banner | banner-sm |
|---|---|---|---|---|
| vantilatör | 1292 KB | ~110 KB | ~110 KB | ~40 KB |
| drenaj | 1223 KB | ~65 KB | ~64 KB | ~28 KB |
| hidrofor | 1145 KB | ~66 KB | ~65 KB | ~29 KB |

## Yerel HTML / runtime

- Google Fonts stylesheet yok; Urbanist self-host + preload
- LCP `currentSrc`: `_optimized/...-banner-sm.webp` (~40 KB)
- Preload: optimize banner + imagesrcset (sm + banner)
- 3 slayt carousel + oklar + noktalar duruyor
- GTM head inject yok (`kosarLoadGtm`); Meta `kosarLoadMetaPixel` (çerez kabulü / idle)
- HomeBanner PHPUnit: 10/10 geçti

## Canlı için kalan

1. Optimize WebP + kod deploy
2. Nginx cache (bkz. `NGINX-CACHE.md`)
3. PSI mobil retest
