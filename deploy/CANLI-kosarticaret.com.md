# kosarticaret.com canlı kurulum

**Canlı adres:** https://kosarticaret.com  
**Kök klasör:** `domains/kosarticaret.com/kosar/`  
**Web kökü (Document Root):** `domains/kosarticaret.com/kosar/public`

---

## 1. DirectAdmin — Document Root (404 bunun yüzünden olur)

Alan adı `kosarticaret.com` için site kökü **mutlaka**:

```
domains/kosarticaret.com/kosar/public
```

`.htaccess` dosyası **`kosar/public/.htaccess`** içinde olmalı (içeriği projede `public/.htaccess`).

Kaydedin. SSL (Let’s Encrypt) açık olsun.

**Test:** `https://kosarticaret.com/kosar/public/` açılıyorsa sadece Document Root yanlıştır.

### `test-kurulum.php` de 404 ise (kesin teşhis)

**`public_html` site kökü değil.** Dosyalar yanlış dizinde.

**A)** DirectAdmin → Alan adı → **Document Root** satırındaki tam yolu kopyalayın.  
Dosya yöneticisinde **o klasöre** gidin → `test-kurulum.php` yükleyin → tekrar deneyin.

**B)** Aynı `test-kurulum.php` dosyasını şu 3 yere de koyup URL deneyin:

| Dosya yolu (sunucu) | Test URL |
|---------------------|----------|
| `kosar/public/test-kurulum.php` | `https://kosarticaret.com/kosar/public/test-kurulum.php` |
| `public_html/test-kurulum.php` | `https://kosarticaret.com/test-kurulum.php` |
| `kosar/test-kurulum.php` | `https://kosarticaret.com/kosar/test-kurulum.php` |

Hangisi **“OK”** yazarsa, canlı site kökü odur; `index.php` ve `.htaccess` **o klasöre** konur.

**C)** Document Root’u kalıcı çözüm olarak şuna ayarlayın:  
`domains/kosarticaret.com/kosar/public`  
→ `kosar/public/index.php` (standart Laravel) + `.env` yeterli.

### LiteSpeed 404 (“Proudly powered by LiteSpeed”) — hâlâ aynıysa

Sunucu **`public_html`** okuyor; Laravel çalışmıyor. **B planı** (5 dk):

1. Bilgisayarınızda: `kosar/deploy/public_html/` içindeki `index.php` ve `.htaccess` → sunucuda **`public_html/`** içine yükleyin (üzerine yazın).
2. `kosar/public/css`, `kosar/public/js`, `kosar/public/images`, `favicon.svg` → hepsini **`public_html/`** içine kopyalayın.
3. SSH: `cd ~/domains/kosarticaret.com/kosar && php artisan storage:link`  
   Sonra `public_html/storage` → `../kosar/storage/app/public` symlink (Dosya Yöneticisi “Link” varsa).
4. `https://kosarticaret.com` tekrar deneyin.

Detay: `deploy/public_html/README.txt`

---

## 2. Sunucudaki `.env` (kopyala-yapıştır şablon)

`kosar/.env` dosyasını düzenleyin:

```env
APP_NAME=Koşar
APP_ENV=production
APP_KEY=base64:...   # boşsa: php artisan key:generate
APP_DEBUG=false
APP_URL=https://kosarticaret.com

APP_LOCALE=tr
APP_FALLBACK_LOCALE=tr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=VERITABANI_ADI
DB_USERNAME=VERITABANI_KULLANICI
DB_PASSWORD=VERITABANI_SIFRE

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="siparis@kosarticaret.com"
MAIL_FROM_NAME="${APP_NAME}"

ADMIN_PASSWORD=GÜÇLÜ_ŞİFRE

KOSAR_NAME=Koşar
KOSAR_EMAIL=info@kosarticaret.com

PAYMENT_PROVIDER=mock
FILESYSTEM_DISK=public
```

**Önemli:** `APP_URL` sonunda `/` olmasın. `http://` değil, `https://kosarticaret.com`.

---

## 3. Terminal (SSH)

```bash
cd ~/domains/kosarticaret.com/kosar

php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Katalog CSV ile:

```bash
php artisan catalog:import-woocommerce --file=wc-product-export-30-5-2026-1780142002073.csv --force
```

---

## 4. İzinler

```bash
chmod -R 775 storage bootstrap/cache
```

---

## 5. Kontrol

| Adres | Beklenen |
|--------|----------|
| https://kosarticaret.com | Ana sayfa |
| https://kosarticaret.com/urunler | Ürünler |
| https://kosarticaret.com/yonetim/giris | Panel |
| http://kosarticaret.com | HTTPS’e yönlenmeli |
| http://www.kosarticaret.com | https://kosarticaret.com |

---

## 6. Yerel projeyi güncellediyseniz

Yeni `public/.htaccess` ve `AppServiceProvider` dosyalarını sunucuya tekrar yükleyin (veya tüm `kosar` klasörünü güncelleyin).
