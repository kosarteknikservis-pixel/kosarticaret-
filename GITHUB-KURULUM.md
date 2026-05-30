# GitHub + canlı site kurulumu (sıfırdan)

**Site:** https://kosarticaret.com  
**GitHub repo (sizin):** `kosarteknikservis-pixel/kosargrup-site`

---

## Önemli: GitHub ekranında ne seçeceksiniz?

Actions → “Choose a workflow” ekranında **Laravel / PHP şablonuna tıklamayın.**

Kod push edilince **`Canlıya gönder`** workflow’u otomatik görünür (`.github/workflows/canliya-gonder.yml`).

---

## Bölüm 1 — Bilgisayar → GitHub (ilk kez)

### 1) Git kurulu mu?

CMD:

```cmd
git --version
```

### 2) Projeyi Git’e bağla

```cmd
cd /d c:\xampp\htdocs\kosarticaretpanelli\kosar
git init
git branch -M main
git add .
git commit -m "Kosar e-ticaret ilk commit"
git remote add origin https://github.com/kosarteknikservis-pixel/kosargrup-site.git
git push -u origin main
```

GitHub girişi istenirse tarayıcıdan onaylayın.

> **Not:** `database.sqlite` ve ürün görselleri Git’e **girmez** (boyut + güvenlik). İlk canlı kurulum zip ile yapılır.

---

## Bölüm 2 — İlk canlı kurulum (ürünler + görseller)

GitHub kod güncellemesi **görselleri taşımaz**. İlk kez:

1. `canli-paket-hazirla.bat` → çift tık  
2. `deploy/kosarticaret-canli-....zip` → DirectAdmin  
3. `domains/kosarticaret.com/` içinde aç → `kosar` + `public_html`  
4. Document Root: `public_html`, PHP 8.3  
5. `kosar/.env` → `ADMIN_PASSWORD` güçlü şifre  

Detay: `deploy/KURULUM-CANLI.md`

---

## Bölüm 3 — Sonraki güncellemeler (GitHub → sunucu)

### Sunucuda SSH açık olmalı

DirectAdmin veya hosting firmasından SSH bilgisi alın.

### GitHub Secrets ekle

Repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

| Secret | Örnek değer |
|--------|-------------|
| `DEPLOY_HOST` | Sunucu IP veya `kosarticaret.com` |
| `DEPLOY_USER` | `admin` |
| `DEPLOY_PATH` | `/home/admin/domains/kosarticaret.com` |
| `DEPLOY_SSH_KEY` | SSH private key (tam metin, `-----BEGIN...`) |

### Canlıya gönder

1. Kodu değiştirin → `git add .` → `git commit` → `git push`  
2. GitHub → **Actions** → **Canlıya gönder** → **Run workflow**  
3. Onay kutusuna: **`canli`** yazın → Run  

Sunucuya gider: `app`, `resources`, `vendor`, `public/css`, `public/js` vb.  
**Dokunulmaz:** sunucudaki `.env`, `database.sqlite`, `storage/app/public` (görseller).

---

## Bölüm 4 — Yerel geliştirme

| İş | Dosya |
|----|--------|
| Canlı zip üret | `canli-paket-hazirla.bat` |
| Yerelde test araçları | `yerel-geri-yukle.bat` |
| Yerel site | `php artisan serve --port=8001` |

Paket script’i bitince yerelde `composer install` (dev) otomatik çalışır.

---

## Cursor ile “canlıya gönder”

Push + Actions çalıştırma talimatını bana yazabilirsiniz; adımları hatırlatırım. Tam otomatik SSH sizin Secrets tanımlamanıza bağlı.

---

## Özet akış

```
[Yerel kod] ──git push──► [GitHub]
                              │
         İlk kurulum          │  Sonraki güncellemeler
              │               │
              ▼               ▼
    canli-paket-hazirla.bat   Actions: Canlıya gönder
              │               │
              ▼               ▼
         DirectAdmin zip      SSH rsync
              │               │
              └──────► kosarticaret.com
```
