# Kosar E-Ticaret (Laravel)

Türkçe e-ticaret vitrini ve **Kosar Panel** — Laravel 13 + Blade + SQLite (geliştirme) / MySQL (üretim).

## Kurulum

```powershell
cd kosar
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=8001
```

> Port **8000** bu bilgisayarda XAMPP’e ait olabilir; Kosar için **8001** kullanın.

- Mağaza: http://127.0.0.1:8001  
- Blog: http://127.0.0.1:8001/blog  
- Panel: http://127.0.0.1:8001/yonetim/giris  

## Panel girişi

| Alan | Değer |
|------|--------|
| E-posta | `admin@kosar.com.tr` |
| Şifre | `kosar-dev` (`.env` → `ADMIN_PASSWORD`) |

## Özellikler

### Vitrin
- Ürün, kategori, marka sayfaları + arama (`/ara`) + filtre/sıralama
- Sepet (sayfa + AJAX ekleme), favoriler, misafir ödeme
- Müşteri hesabı: `/giris`, `/kayit`, `/hesabim` (sipariş geçmişi)
- Sipariş takip, iletişim formu, blog, CMS sözleşme sayfaları
- SEO: canonical, Open Graph, JSON-LD, `sitemap.xml`, `robots.txt`
- Çerez bildirimi, WhatsApp butonu (panel ayarlarından)

### Panel (`/yonetim`)
- Ürün / kategori / marka / kupon CRUD
- Ürün görseli yükleme (`storage/app/public/products`)
- CMS sayfaları ve site ayarları
- Blog, sipariş yönetimi
- Ödeme: `mock`, `iyzico`, `paytr` (`PAYMENT_PROVIDER`)

## MySQL (üretim)

`.env` içinde:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kosar
DB_USERNAME=...
DB_PASSWORD=...
```

Ardından `php artisan migrate --seed`.

## Demo kupon

`KOSAR10` — %10 indirim (min. 500 TL sepet)
