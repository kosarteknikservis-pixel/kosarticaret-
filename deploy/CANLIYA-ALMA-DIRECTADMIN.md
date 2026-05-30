# kosarticaret.com — Sıfırdan Kurulum (DirectAdmin)

Bu paket yerel geliştirme veritabanı (1390 ürün) ve görsellerle hazırlanmıştır.

## Paket içeriği

```
kosarticaret-canli.zip
├── kosar/              Laravel uygulaması (.env, vendor, database.sqlite, görseller)
├── public_html/        Site kökü (index.php, css, js, images)
└── KURULUM.md          Bu dosya
```

---

## 1. Canlıdaki eski dosyaları silin

DirectAdmin → Dosya Yöneticisi → `domains/kosarticaret.com/`

Silin (varsa):
- Eski `kosar/` klasörü
- Eski `public_html/` içeriği (klasörü silmeyin, içini temizleyin)
- Eski `kosar.zip`, `asdasda.zip` vb.

---

## 2. Zip'i yükleyin ve açın

1. `kosarticaret-canli.zip` dosyasını `domains/kosarticaret.com/` içine yükleyin
2. Zip'e sağ tık → **Extract / Aç**
3. Sonuç:
   - `domains/kosarticaret.com/kosar/`
   - `domains/kosarticaret.com/public_html/` (css, js, index.php…)

---

## 3. Document Root

DirectAdmin → **Alan Adı Ayarları** → kosarticaret.com:

```
domains/kosarticaret.com/public_html
```

**PHP sürümü: 8.3** (PHP Ayarları'ndan seçin)

SSL (Let's Encrypt) açık olsun.

---

## 4. SSH — son komutlar

```bash
su - admin

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

**ÖNEMLİ:** `migrate`, `migrate:fresh`, `db:seed` **ÇALIŞTIRMAYIN** — veritabanı hazır gelir.

---

## 5. Test

| URL | Beklenen |
|-----|----------|
| https://kosarticaret.com | Ana sayfa |
| https://kosarticaret.com/urunler | ~1390 ürün |
| https://kosarticaret.com/yonetim/giris | Panel |

**Panel girişi:**
- E-posta: `admin@kosar.com.tr`
- Şifre: yerel geliştirmede kullandığınız şifre (`kosar-dev` veya `.env` içindeki `ADMIN_PASSWORD`)

---

## Sorun giderme

| Hata | Çözüm |
|------|--------|
| 404 | Document Root → `public_html` |
| 500 PHP sürümü | Domain PHP → 8.3 |
| 500 kosar bulunamadı | `public_html/index.php` var mı, `kosar/` yan yana mı |
| Ürün var görsel yok | `storage:link` + `public_html/storage` symlink |
| Boş site | `database.sqlite` `kosar/database/` içinde mi, `.env` → `DB_CONNECTION=sqlite` |

---

## Güncelleme (ileride)

Yeni zip aldığınızda:
1. `.env` dosyanızı yedekleyin
2. `kosar/` ve `public_html/` güncelleyin
3. `.env` geri koyun
4. `config:cache` tekrar çalıştırın
