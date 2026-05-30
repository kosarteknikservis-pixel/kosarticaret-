# kosarticaret.com — Hostinger canlı kurulum

## 1) Yerelde zip (PC)

`canli-paket-hazirla.bat` çift tık → `deploy/kosarticaret-canli-YYYY-MM-DD.zip`

## 2) Hostinger dosya yöneticisi

**Websites → kosarticaret.com → File Manager**

Hedef klasör (genelde):

```
domains/kosarticaret.com/
```

veya Hostinger panelde gösterilen **document root’un bir üstü**.

### Temizlik (eski deneme varsa)

- Eski `kosar.zip`, `yedekler.zip` silin
- Eski `kosar/` silin
- `public_html/` **içini** boşaltın (klasör kalsın)

### Yükleme

1. Zip’i `domains/kosarticaret.com/` içine **Upload**
2. Zip → **Extract / Unzip**
3. Sonuç **yan yana**:

```
domains/kosarticaret.com/
├── kosar/
└── public_html/
```

4. Zip dosyasını silin (yer kazanır)

## 3) Site ayarları (hPanel)

| Ayar | Değer |
|------|--------|
| Document root | `public_html` (tam yol panelde yazar) |
| PHP | **8.3** |
| SSL | Açık (Let’s Encrypt) |

## 4) `.env` şifre

`kosar/.env` dosyasını düzenleyin:

```env
ADMIN_PASSWORD=GÜÇLÜ_ŞİFRE
APP_URL=https://kosarticaret.com
APP_DEBUG=false
```

Kaydedin.

## 5) SSH (Hostinger → Advanced → SSH)

SSH açıksa terminalde:

```bash
cd ~/domains/kosarticaret.com/kosar
chmod 664 database/database.sqlite
chmod -R 775 storage bootstrap/cache database
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Görseller:** Zip’te `public_html/storage/` zaten var — ekstra link gerekmez.

SSH yoksa bu adımı atlayın; zip yeterli olmalı.

## 6) Test

- https://kosarticaret.com
- https://kosarticaret.com/urunler
- https://kosarticaret.com/yonetim/giris  
  Kullanıcı: `admin@kosar.com.tr` — şifre: `.env` içindeki `ADMIN_PASSWORD`

## 7) GitHub otomatik deploy (sonra)

hPanel → SSH → anahtar oluştur → GitHub Secrets:

- `DEPLOY_HOST` — SSH host (panelde yazar)
- `DEPLOY_USER` — SSH kullanıcı adı
- `DEPLOY_PATH` — `/home/KULLANICI/domains/kosarticaret.com` (paneldeki tam yol)
- `DEPLOY_SSH_KEY` — private key

Actions → **Canlıya gönder** → `canli`

---

## Sorun

| Belirti | Çözüm |
|---------|--------|
| 404 | Document root = `public_html` |
| Beyaz sayfa / vendor hatası | Tam zip’ten yeniden `kosar/` + `public_html/` |
| Görsel yok | `public_html/storage/products` var mı? |
| 500 | PHP 8.3, `kosar/.env` ve `APP_KEY` dolu mu? |
