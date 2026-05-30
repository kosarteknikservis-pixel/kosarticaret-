# Deploy sistemi özeti

## 1. İlk canlı (tam site)

`canli-paket-hazirla.bat` → `deploy/*.zip` → DirectAdmin

## 2. Kod güncellemesi

`git push` → GitHub Actions → **Canlıya gönder** (onay: `canli`)

Gerekli: GitHub Secrets (`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, `DEPLOY_SSH_KEY`)

## 3. Yerel

`yerel-geri-yukle.bat` — geliştirme modu

GitHub: yeni repo `kosarticaret` (kosargrup değil). Ayrıntı: `GITHUB-KURULUM.md`
