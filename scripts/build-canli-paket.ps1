# kosarticaret.com tam canli paket (SQLite + urunler + gorseller)
# Kullanim: powershell -ExecutionPolicy Bypass -File scripts\build-canli-paket.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$date = Get-Date -Format "yyyy-MM-dd"
$outDir = Join-Path $root "deploy"
$stagingRoot = Join-Path $outDir "_canli_staging"
$stagingKosar = Join-Path $stagingRoot "kosar"
$stagingPublic = Join-Path $stagingRoot "public_html"
$zipPath = Join-Path $outDir "kosarticaret-canli-$date.zip"

$sqliteSource = Join-Path $root "database\database.sqlite"
if (-not (Test-Path $sqliteSource)) {
    throw "database/database.sqlite bulunamadi. Once yerel katalog import edilmis olmali."
}

Write-Host "==> Composer (production, no dev)..."
cmd /c "composer install --no-dev --optimize-autoloader --no-interaction"
if ($LASTEXITCODE -ne 0) { throw "composer install failed" }

if (Test-Path $stagingRoot) {
    Remove-Item $stagingRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $stagingKosar -Force | Out-Null
New-Item -ItemType Directory -Path $stagingPublic -Force | Out-Null

Write-Host "==> kosar/ kopyalaniyor..."
robocopy $root $stagingKosar /E /XD node_modules .git .idea .vscode .cursor .codex .phpunit.cache tests deploy scripts _canli_staging "public\storage" `
    /XF .env .env.backup *.log .phpunit.result.cache wc-product-export*.csv tmp-*.php phpunit.xml canli-*.bat yerel-*.bat `
    /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy kosar failed" }

Write-Host "==> database.sqlite kopyalaniyor..."
Copy-Item $sqliteSource (Join-Path $stagingKosar "database\database.sqlite") -Force

Write-Host "==> .env (canli SQLite) hazirlaniyor..."
$envTemplate = Get-Content (Join-Path $root "deploy\.env.canli.sqlite") -Raw
$keyOutput = & php artisan key:generate --show 2>$null
if (-not $keyOutput -or $keyOutput -notmatch "base64:") {
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    $keyOutput = "base64:$([Convert]::ToBase64String($bytes))"
}
$envContent = $envTemplate -replace "APP_KEY=\r?\n", "APP_KEY=$keyOutput`n"
Set-Content -Path (Join-Path $stagingKosar ".env") -Value $envContent.TrimEnd() -Encoding UTF8 -NoNewline
Add-Content -Path (Join-Path $stagingKosar ".env") -Value "`n" -Encoding UTF8

Write-Host "==> public_html/ hazirlaniyor..."
$publicSrc = Join-Path $root "public"
robocopy $publicSrc $stagingPublic /E /XD storage `
    /XF index.php `
    /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
Copy-Item (Join-Path $root "deploy\public_html\index.php") (Join-Path $stagingPublic "index.php") -Force
Copy-Item (Join-Path $root "deploy\public_html\.htaccess") (Join-Path $stagingPublic ".htaccess") -Force

Write-Host "==> public_html/storage (gorseller, SSH gerektirmez)..."
$storagePublic = Join-Path $root "storage\app\public"
$stagingStorage = Join-Path $stagingPublic "storage"
if (Test-Path $storagePublic) {
    robocopy $storagePublic $stagingStorage /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
}

Write-Host "==> cache/log temizligi..."
@(
    (Join-Path $stagingKosar "storage\logs"),
    (Join-Path $stagingKosar "storage\framework\cache\data"),
    (Join-Path $stagingKosar "storage\framework\sessions"),
    (Join-Path $stagingKosar "storage\framework\views"),
    (Join-Path $stagingKosar "bootstrap\cache")
) | ForEach-Object {
    if (Test-Path $_) {
        Get-ChildItem $_ -Recurse -Force -ErrorAction SilentlyContinue | Remove-Item -Force -Recurse -ErrorAction SilentlyContinue
    }
}
New-Item -ItemType File -Path (Join-Path $stagingKosar "storage\logs\.gitignore") -Force | Out-Null

Copy-Item (Join-Path $root "deploy\KURULUM-CANLI.md") (Join-Path $stagingRoot "KURULUM.md") -Force
if (-not (Test-Path (Join-Path $stagingRoot "KURULUM.md"))) {
    Copy-Item (Join-Path $root "deploy\CANLIYA-ALMA-DIRECTADMIN.md") (Join-Path $stagingRoot "KURULUM.md") -Force
}
Copy-Item (Join-Path $root "deploy\sunucu-kurulum.sh") (Join-Path $stagingRoot "sunucu-kurulum.sh") -Force

if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir -Force | Out-Null }
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Write-Host "==> Zip olusturuluyor..."
Set-Location $stagingRoot
tar.exe -a -cf $zipPath -C $stagingRoot "kosar" "public_html" "KURULUM.md" "sunucu-kurulum.sh"
Set-Location $root
if (-not (Test-Path $zipPath)) { throw "zip olusturulamadi" }

Remove-Item $stagingRoot -Recurse -Force

$sqliteMb = [math]::Round((Get-Item $sqliteSource).Length / 1MB, 2)
$zipMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
$productsDir = Join-Path $root "storage\app\public\products"
$productCount = if (Test-Path $productsDir) {
    (Get-ChildItem $productsDir -Recurse -File -ErrorAction SilentlyContinue | Measure-Object).Count
} else { 0 }

Write-Host ""
Write-Host "========================================"
Write-Host "  CANLI PAKET HAZIR"
Write-Host "========================================"
Write-Host "Zip:     $zipPath"
Write-Host "Boyut:   $zipMb MB"
Write-Host "SQLite:  $sqliteMb MB"
Write-Host "Gorsel:  $productCount dosya"
Write-Host ""
Write-Host "DirectAdmin: domains/kosarticaret.com/ icine yukleyip acin."
Write-Host "KURULUM.md dosyasini okuyun."
Write-Host "========================================"

Write-Host "==> Yerel vendor (dev) geri yukleniyor..."
cmd /c "composer install --no-interaction >nul 2>&1"
