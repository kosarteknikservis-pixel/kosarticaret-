# kosarticaret.com — Canlı kurulum (DirectAdmin)

**Site:** https://kosarticaret.com  
**Sunucu kökü:** `domains/kosarticaret.com/`  
**Web kökü (Document Root):** `domains/kosarticaret.com/public_html`

---

## Zip’e ne girmeli? (Önemli)

**`kosar` klasörünü olduğu gibi komple zip’lemeyin** (`.env`, `.git`, `tests`, `node_modules`, boş `public/storage` bağlantısı, log/cache vb. girer → hata ve gereksiz 1 GB+ boyut).

Doğru paket yapısı:

```
kosarticaret-canli.zip          ← zip’i domains/kosarticaret.com/ içine yüklersiniz
├── kosar/                      ← Laravel (vendor, storage görseller, database.sqlite)
├── public_html/                ← Site kökü (index.php, css, js — deploy/public_html/index.php)
├── KURULUM.md                  ← Bu dosyanın kopyası (isteğe bağlı)
└── sunucu-kurulum.sh           ← SSH komutları (isteğe bağlı)
```

### `kosar/` içine girenler

- `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `vendor`
- `database/database.sqlite` (yerel katalog + ürünler)
- `storage/app/public/` (ürün görselleri, logo, banner)
- `artisan`, `composer.json`, `composer.lock`

### `kosar/` dışında bırakın

| Hariç | Neden |
|--------|--------|
| `.env` | Canlıda ayrı oluşturulur (şablon: `deploy/.env.canli.sqlite`) |
| `.git`, `tests`, `node_modules` | Gereksiz |
| `deploy/`, `.cursor`, `.vscode` | Geliştirme |
| `public/storage` | Sunucuda `php artisan storage:link` |
| `*.log`, `storage/framework/views/*` | Önbellek |
| `wc-product-export*.csv` | Import dosyası |

### `public_html/` nasıl hazırlanır?

1. Yerel `public/` içinden **kopyalayın:** `css/`, `js/`, `favicon.svg`, `robots.txt` (varsa `images/`)
2. **`public/index.php` ve `public/.htaccess` KOPYALAMAYIN**
3. Bunların yerine koyun: `deploy/public_html/index.php` ve `deploy/public_html/.htaccess`

`deploy/public_html/index.php` Laravel’i yanındaki `kosar/` klasöründen çalıştırır.

---

## Zip’i otomatik oluşturma (önerilen)

Yerelde **`canli-paket-hazirla.bat`** dosyasına çift tıklayın.

Çıktı: `deploy/kosarticaret-canli-YYYY-MM-DD.zip`  
İçinde `kosar`, `public_html` (görseller dahil), `.env`, `KURULUM.md` hazır gelir.  
Yerel geliştirme paket sonrası otomatik açılır; gerekirse `yerel-geri-yukle.bat`.

---

## DirectAdmin — adım adım

### 1) Eski dosyaları temizleyin

`domains/kosarticaret.com/` içinde:

- Eski `kosar/` klasörü (varsa silin)
- `public_html/` **içini** boşaltın (klasör kalsın)
- Eski zip’ler: `asdasda.zip` vb.

### 2) Zip yükle ve aç

1. `kosarticaret-canli-....zip` → `domains/kosarticaret.com/` içine **Yükle**
2. Zip’e sağ tık → **Extract / Aç**
3. Sonuç **yan yana** iki klasör olmalı:

```
domains/kosarticaret.com/
├── kosar/
└── public_html/
```

Zip’i açtıktan sonra zip dosyasını silebilirsiniz.

### 3) Document Root

DirectAdmin → **Alan Adı Ayarları** → kosarticaret.com:

```
domains/kosarticaret.com/public_html
```

- **PHP 8.3**
- **SSL** (Let’s Encrypt) açık

### 4) `.env` kontrolü

`kosar/.env` içinde en az:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kosarticaret.com
DB_CONNECTION=sqlite
ADMIN_PASSWORD=GÜÇLÜ_ŞİFRE_BURAYA
```

`APP_URL` sonunda `/` olmasın.

Otomatik paket kullandıysanız `.env` zip’te gelir; **panel şifresini mutlaka değiştirin.**

### 5) SSH (son komutlar)

```bash
cd /home/admin/domains/kosarticaret.com/kosar

chmod 664 database/database.sqlite
chmod -R 775 storage bootstrap/cache database

/usr/local/php83/bin/php artisan storage:link

cd /home/admin/domains/kosarticaret.com
ln -sf ../kosar/storage/app/public public_html/storage

cd /home/admin/domains/kosarticaret.com/kosar
/usr/local/php83/bin/php artisan config:cache
/usr/local/php83/bin/php artisan route:cache
/usr/local/php83/bin/php artisan view:cache
```

Veya zip’teki `sunucu-kurulum.sh` dosyasını aynı dizinde çalıştırın.

**ÇALIŞTIRMAYIN:** `migrate`, `migrate:fresh`, `db:seed` — veritabanı zip ile hazır gelir.

SSH yoksa: DirectAdmin’de `public_html/storage` → symlink hedef: `../kosar/storage/app/public`

### 6) Test

| URL | Beklenen |
|-----|----------|
| https://kosarticaret.com | Ana sayfa |
| https://kosarticaret.com/urunler | Ürün listesi |
| https://kosarticaret.com/yonetim/giris | Panel |

Panel: `admin@kosar.com.tr` + `.env` içindeki `ADMIN_PASSWORD`

---

## Sorun giderme

| Belirti | Çözüm |
|---------|--------|
| LiteSpeed 404 | Document Root = `public_html`; `public_html/index.php` deploy sürümü mü? |
| 500 kosar bulunamadı | `kosar` ile `public_html` **aynı seviyede** mi? |
| CSS/JS yok | `kosar/public/css` → `public_html/css` kopyalandı mı? |
| Görseller yok | `storage:link` + `public_html/storage` symlink |
| Boş katalog | `kosar/database/database.sqlite` var mı, izin 664? |

---

## Alternatif (daha temiz, mümkünse)

Document Root’u değiştirebiliyorsanız:

```
domains/kosarticaret.com/kosar/public
```

Bu durumda `public_html` planına gerek kalmaz; standart Laravel `public/index.php` kullanılır. Çoğu DirectAdmin hesabında varsayılan `public_html` olduğu için yukarıdaki **iki klasör** modeli önerilir.

---

## MySQL kullanacaksanız

Zip’e `database.sqlite` koymayın. Sunucuda MySQL veritabanı açın, `deploy/.env.production.example` şablonunu `kosar/.env` yapın, SSH:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
```

Katalog CSV import: `php artisan catalog:import-woocommerce --file=... --force`
