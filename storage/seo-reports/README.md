# SEO Raporlari ve Izleme (Faz 4)

Haftalik Google Search Console verisi, deploy sonrasi SEO regresyon kontrolu ve legacy redirect dogrulama.

## Haftalik rutin (10 dakika)

### 1. GSC performans + kategori kelime takibi

1. [Google Search Console](https://search.google.com/search-console) → **kosarticaret.com**
2. **Performans** → tarih: **Son 28 gun**
3. Sag ust **Disa aktar** → **Excel (.xlsx)**
4. Dosyayi `storage/seo-reports/inbox/` klasorune kopyalayin
5. Terminal:

```bash
php artisan seo:import-gsc-performance
```

6. Olusan ozet: `storage/seo-reports/gsc-performance-latest.json`
7. Terminal ciktisinda **kategori kelime takibi** tablosunu kontrol edin (`config/seo_monitoring.php` → `category_keywords`)

**Hedef:** Ticari kelimeler sirasiyla page 2 (11-20) → ilk 10 → ilk 5.

### 2. PageSpeed / CrUX (otomatik veya manuel)

Panel → **Performans → Site hizi** veya:

```bash
php artisan pagespeed:audit
```

- API anahtari: Site ayarlari → Entegrasyonlar → PageSpeed
- Canli URL: `PAGESPEED_AUDIT_BASE_URL=https://kosarticaret.com`
- Zamanlayici: Her Pazartesi 05:30 (`pagespeed:audit`)

## Deploy sonrasi (5 dakika)

Canliya gonderimden sonra:

```bash
php artisan seo:drift-check --base-url=https://kosarticaret.com
php artisan seo:check-redirects --base-url=https://kosarticaret.com
```

Regresyon varsa CI/CD veya deploy checklist'te durdurmak icin:

```bash
php artisan seo:drift-check --base-url=https://kosarticaret.com --fail-on-regression
```

### Drift baseline (ilk kurulum / buyuk SEO degisikligi sonrasi)

```bash
php artisan seo:drift-baseline --base-url=https://kosarticaret.com
```

Baseline dosyasi: `storage/seo-reports/drift-baseline.json` (git'e girmez)

Kontrol edilen sinyaller: `title`, `meta description`, `canonical`, `robots`, `H1`, JSON-LD tipleri, HTTP status.

## Aylik teknik kontrol

GSC → **Sayfa dizine ekleme** / **Dizin olusturma**:

- 404 export → `storage/seo-reports/inbox/coverage-404.xlsx`
- Noindex export → `storage/seo-reports/inbox/coverage-noindex.xlsx`

404 URL listesi icin:

```bash
php artisan seo:sync-gsc-redirects storage/app/gsc-404-urls.txt
php artisan seo:check-redirects --base-url=https://kosarticaret.com
```

## 3 ayda bir (rakip SERP)

Manuel veya Cursor SEO skill ile kategori head-term karsilastirmasi:

- `hidrofor fiyatlari`, `dalgic pompa`, `su pompasi`, `endustriyel vantilator`
- Sonuclari `storage/seo-reports/` altina not olarak kaydedin

## GSC baglanti (ilk kurulum)

1. Search Console'da property: `https://kosarticaret.com`
2. Panel → **Site ayarlari → Google** → dogrulama
3. GSC → **Site haritalari** → `https://kosarticaret.com/sitemap.xml`
4. IndexNow: yeni blog/urun URL bildirimi (`seo:notify-fresh`)

## Komut ozeti

| Komut | Amac |
|-------|------|
| `seo:import-gsc-performance` | GSC xlsx → JSON + kelime takibi |
| `seo:drift-baseline` | SEO sinyal referansi olustur |
| `seo:drift-check` | Deploy sonrasi regresyon |
| `seo:check-redirects` | Legacy 301 ve zincir kontrolu |
| `pagespeed:audit` | Lab + CrUX olcumu |
| `seo:sync-gsc-redirects` | 404 listesinden urun redirect |

## Klasor yapisi

```
storage/seo-reports/
  inbox/                        ← GSC xlsx dosyalari
  gsc-performance-latest.json   ← haftalik performans ozeti
  drift-baseline.json           ← SEO referans (manuel olusturulur)
  drift-check-latest.json       ← son drift raporu
  redirect-check-latest.json    ← son redirect raporu
```
