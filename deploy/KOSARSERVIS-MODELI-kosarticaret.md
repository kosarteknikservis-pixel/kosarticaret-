# kosarservis.com modeli → kosarticaret.com

Çalışan sitedeki yapı:

```
domains/kosarservis.com/
├── sistem/              ← Laravel (app, vendor, storage, .env …)
└── public_html/         ← Site kökü (Document Root)
    ├── index.php        → ../sistem/ yolları
    ├── .htaccess
    ├── css/, js/, build/, storage/ …
```

`kosarticaret.com` için aynısı (`kosar` = `sistem`):

```
domains/kosarticaret.com/
├── kosar/               ← Laravel (zaten var)
└── public_html/         ← Document Root BURASI olmalı
    ├── index.php        → ../kosar/ yolları
    ├── .htaccess
    ├── css/, js/, images/ … (kosar/public’ten kopya)
    └── storage/         → ../kosar/storage/app/public (link)
```

---

## Adım 1 — kosarservis ile kosarticaret karşılaştırması

DirectAdmin → **Alan adı ayarları**:

| Alan adı | Document Root |
|----------|----------------|
| kosarservis.com | Muhtemelen `.../kosarservis.com/public_html` |
| kosarticaret.com | **Aynı mantık olmalı** → `.../kosarticaret.com/public_html` |

`test-kurulum.php` 404 ise kosarticaret’in kökü **public_html değil** — önce bunu kosarservis ile aynı yapın.

---

## Adım 2 — public_html içeriği

1. `kosar/public/` içindeki **tüm dosya ve klasörleri** seçin  
   (`css`, `js`, `images`, `favicon.svg`, `.htaccess` …)  
   → **Kopyala** → `public_html/` içine **yapıştır**.

2. `public_html/index.php` dosyasını **silin**, yerine `deploy/public_html/index.php` içeriğini koyun  
   (`$kosarRoot = dirname(__DIR__).'/kosar';`)

3. `public_html/.htaccess` = `kosar/public/.htaccess` veya `deploy/public_html/.htaccess`

**Dikkat:** `index.php` içinde `../vendor` değil **`../kosar/vendor`** olmalı (sistem’de `../sistem/vendor` gibi).

---

## Adım 3 — storage (kosarservis’te public_html/storage var)

SSH:

```bash
cd ~/domains/kosarticaret.com/kosar
php artisan storage:link
```

`public_html/storage` zaten `kosar/storage/app/public`’e link olmalı.  
Link yoksa DirectAdmin’de: `public_html/storage` → hedef `../kosar/storage/app/public`

---

## Adım 4 — .env

`kosar/.env` (sistem/.env gibi Laravel kökünde):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kosarticaret.com
DB_CONNECTION=mysql
```

---

## Adım 5 — Test

1. `https://kosarticaret.com/test-kurulum.php` (public_html’de)  
2. `https://kosarticaret.com`  
3. `https://kosarticaret.com/yonetim/giris`

---

## kosarservis index.php muhtemelen böyle

```php
require __DIR__.'/../sistem/vendor/autoload.php';
$app = require_once __DIR__.'/../sistem/bootstrap/app.php';
```

kosarticaret için:

```php
require __DIR__.'/../kosar/vendor/autoload.php';
$app = require_once __DIR__.'/../kosar/bootstrap/app.php';
```

Tam sürüm: `deploy/public_html/index.php`
