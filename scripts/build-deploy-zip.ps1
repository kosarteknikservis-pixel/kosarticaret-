# Kosar canli deploy zip (DirectAdmin)
# Kullanim: powershell -ExecutionPolicy Bypass -File scripts\build-deploy-zip.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$date = Get-Date -Format "yyyy-MM-dd"
$outDir = Join-Path $root "deploy"
$stagingRoot = Join-Path $outDir "_staging"
$staging = Join-Path $stagingRoot "kosar"
$zipName = "kosar-canli-$date.zip"
$zipPath = Join-Path $outDir $zipName

Write-Host "==> Composer (production, no dev)..."
cmd /c "composer install --no-dev --optimize-autoloader --no-interaction >nul 2>&1"
if ($LASTEXITCODE -ne 0) { throw "composer install failed" }

if (Test-Path $stagingRoot) {
    Remove-Item $stagingRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $staging -Force | Out-Null

Write-Host "==> Dosyalar kopyalaniyor..."
robocopy $root $staging /E /XD node_modules .git .idea .vscode .cursor .codex .phpunit.cache tests deploy _staging "public\storage" `
    /XF .env .env.backup *.log .phpunit.result.cache wc-product-export*.csv tmp-*.php phpunit.xml `
    /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy failed with code $LASTEXITCODE" }

Copy-Item (Join-Path $root "deploy\.env.production.example") (Join-Path $staging ".env.production.example") -Force
Copy-Item (Join-Path $root "deploy\CANLIYA-ALMA-DIRECTADMIN.md") (Join-Path $staging "CANLIYA-ALMA-DIRECTADMIN.md") -Force

# Bos log / cache temizligi
@(
    "storage\logs",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views"
) | ForEach-Object {
    $p = Join-Path $staging $_
    if (Test-Path $p) {
        Get-ChildItem $p -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
    }
}

# SQLite yerel DB zip'e girmesin (canlida MySQL)
$sqlite = Join-Path $staging "database\database.sqlite"
if (Test-Path $sqlite) { Remove-Item $sqlite -Force }

if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir -Force | Out-Null }
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Write-Host "==> Zip olusturuluyor: $zipPath"
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Set-Location $stagingRoot
tar.exe -a -cf $zipPath -C $stagingRoot "kosar"
Set-Location $root
if (-not (Test-Path $zipPath)) { throw "zip olusturulamadi" }

Remove-Item $stagingRoot -Recurse -Force

$mb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Host ""
Write-Host "Tamam: $zipPath ($mb MB)"
Write-Host "DirectAdmin: domains/kosarticaret.com/ yukleyip acin, CANLIYA-ALMA-DIRECTADMIN.md okuyun."

# Gelistirme icin vendor (dev) geri
Write-Host "==> Yerel vendor (dev) geri yukleniyor..."
cmd /c "composer install --no-interaction >nul 2>&1"
