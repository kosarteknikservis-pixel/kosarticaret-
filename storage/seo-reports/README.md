# SEO Raporlari (GSC)

Haftalik Google Search Console verisi buraya konur. Cursor / blog planlama bu dosyalari okur.

## Haftalik rutin (5 dakika)

1. [Google Search Console](https://search.google.com/search-console) → **kosarticaret.com**
2. **Performans** → tarih: **Son 28 gun**
3. Sag ust **Disa aktar** → **Excel (.xlsx)**
4. Dosyayi `storage/seo-reports/inbox/` klasorune kopyalayin
5. Terminal:

```bash
php artisan seo:import-gsc-performance
```

6. Olusan ozet: `storage/seo-reports/gsc-performance-latest.json`

## Aylik teknik kontrol

GSC → **Sayfa dizine ekleme** veya **Dizin oluşturma**:

- 404 export → `storage/seo-reports/inbox/coverage-404.xlsx`
- Noindex export → `storage/seo-reports/inbox/coverage-noindex.xlsx`

404 URL listesi icin mevcut komut:

```bash
php artisan seo:sync-gsc-redirects storage/app/gsc-404-urls.txt
```

## GSC baglanti (ilk kurulum)

1. Search Console'da property ekleyin: `https://kosarticaret.com`
2. Panel → **Site ayarlari → Google** → dogrulama kodu veya HTML dosyasi
3. GSC → **Site haritalari** → `https://kosarticaret.com/sitemap.xml` gonderin
4. IndexNow zaten acik (yeni blog/urun URL bildirimi)

## Klasor yapisi

```
storage/seo-reports/
  inbox/          ← GSC xlsx dosyalarini buraya atin
  gsc-performance-latest.json   ← komutun urettigi ozet (git'e girmez)
```
