# GitHub + kosarticaret.com

**Canlı site:** https://kosarticaret.com  
**GitHub:** Yeni repo oluşturacaksınız — `kosargrup` / `kosargrup-site` **kullanılmayacak.**

Önerilen repo adı: **`kosarticaret`** veya **`kosarticaret-site`**

---

## 1) GitHub’da yeni repo oluşturun

1. https://github.com/new  
2. **Repository name:** `kosarticaret` (veya `kosarticaret-site`)  
3. **Private** seçin (önerilir)  
4. **README, .gitignore, license EKLEMEYİN** (boş repo)  
5. **Create repository**

Oluşan adres örneği:

`https://github.com/kosarteknikservis-pixel/kosarticaret.git`

---

## 2) Bilgisayardan GitHub’a bağlayın

CMD — her satır ayrı Enter:

```cmd
cd /d c:\xampp\htdocs\kosarticaretpanelli\kosar
```

```cmd
git add .
```

```cmd
git commit -m "Kosar e-ticaret ilk yukleme"
```

```cmd
git remote add origin https://github.com/kosarteknikservis-pixel/kosarticaret.git
```

```cmd
git push -u origin main
```

> `kosarteknikservis-pixel` yerine kendi GitHub kullanıcı/organizasyon adınızı yazın.  
> Repo adını `kosarticaret-site` yaptıysanız URL’de onu kullanın.

`remote add` “already exists” derse:

```cmd
git remote remove origin
git remote add origin https://github.com/kosarteknikservis-pixel/kosarticaret.git
git push -u origin main
```

Push bitince GitHub → **Actions** → **Canlıya gönder** görünür.

---

## 3) İlk canlı kurulum (zip — GitHub şart değil)

1. **`canli-paket-hazirla.bat`** çift tık  
2. `deploy/kosarticaret-canli-....zip` → DirectAdmin  
3. `domains/kosarticaret.com/` içinde aç  
4. Document Root: `public_html`, PHP 8.3  
5. `kosar/.env` → `ADMIN_PASSWORD`  

Detay: `deploy/KURULUM-CANLI.md`

---

## 4) Sonraki güncellemeler (isteğe bağlı)

GitHub → Settings → Secrets → Actions:

| Secret | Örnek |
|--------|--------|
| `DEPLOY_HOST` | Sunucu IP |
| `DEPLOY_USER` | `admin` |
| `DEPLOY_PATH` | `/home/admin/domains/kosarticaret.com` |
| `DEPLOY_SSH_KEY` | SSH private key |

Actions → **Canlıya gönder** → onay: `canli`

---

## Yerel geliştirme

| İş | Dosya |
|----|--------|
| Canlı zip | `canli-paket-hazirla.bat` |
| Yerel mod | `yerel-geri-yukle.bat` |

---

## Özet

| Konu | Doğru |
|------|--------|
| Repo | **Yeni:** `kosarticaret` |
| Kullanılmayacak | `kosargrup-site`, `kosargrup` |
| İlk canlı | Zip + DirectAdmin |
| GitHub bağlantısı | Siz `git push` ile yaparsınız |
