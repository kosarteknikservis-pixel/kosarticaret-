# KOSAR - Canlı Paket Hazırlama

$ROOT    = Split-Path -Parent $MyInvocation.MyCommand.Path
$TARIH   = Get-Date -Format "yyyy-MM-dd"
$STAGING = "$ROOT\deploy\_canli_staging"
$ZIPFILE = "$ROOT\deploy\kosarticaret-canli-$TARIH.zip"

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  KOSAR - Canli Paket Hazirlama" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Zip: deploy\kosarticaret-canli-$TARIH.zip"
Write-Host ""

# Eski kalıntıları temizle
Stop-Process -Name robocopy -Force -ErrorAction SilentlyContinue
if (Test-Path $STAGING) { Remove-Item $STAGING -Recurse -Force }
if (Test-Path $ZIPFILE)  { Remove-Item $ZIPFILE  -Force }

# Kontrol
if (-not (Test-Path "$ROOT\artisan"))                      { Write-Host "HATA: artisan yok" -ForegroundColor Red; exit 1 }
if (-not (Test-Path "$ROOT\deploy\public_html\index.php")) { Write-Host "HATA: deploy\public_html\index.php yok" -ForegroundColor Red; exit 1 }
Write-Host "[OK] Kontroller gecti." -ForegroundColor Green

# === 1) Composer ===
Write-Host ""
Write-Host "[1/6] Composer (no-dev)..." -ForegroundColor Yellow
if (Get-Command composer -ErrorAction SilentlyContinue) {
    & composer install --no-dev --optimize-autoloader --no-interaction --quiet
} elseif (Test-Path "$ROOT\composer.phar") {
    & php "$ROOT\composer.phar" install --no-dev --optimize-autoloader --no-interaction --quiet
} else {
    Write-Host "      UYARI: composer bulunamadi, vendor oldugu gibi paketlenecek." -ForegroundColor DarkYellow
}
Write-Host "      Tamam." -ForegroundColor Green

# === 2) Staging ===
Write-Host ""
Write-Host "[2/6] Staging olusturuluyor..." -ForegroundColor Yellow
New-Item -ItemType Directory "$STAGING\kosar"      -Force | Out-Null
New-Item -ItemType Directory "$STAGING\public_html" -Force | Out-Null
Write-Host "      Tamam." -ForegroundColor Green

# === 3) kosar\ kopyala ===
Write-Host ""
Write-Host "[3/6] kosar\ kopyalaniyor (vendor + gorseller - birkaç dk)..." -ForegroundColor Yellow

& robocopy $ROOT "$STAGING\kosar" /E /NP /NFL /NDL /NJH /NJS `
    /XD ".git" "node_modules" "tests" "deploy" ".cursor" ".vscode" ".idea" ".nova" ".zed" `
        "storage\logs" "storage\framework\views" "storage\framework\cache" `
        "storage\framework\sessions" "storage\framework\testing" "storage\pail" `
        "bootstrap\cache" "public\storage" `
    /XF ".env" "*.log" "hot" ".phpunit*" ".phpactor*" "Thumbs.db" ".DS_Store" `
        "auth.json" "wc-product-export*.csv" "canli-paket-hazirla.bat" `
        "canli-paket-hazirla.ps1" "_ide_helper.php"

$rc = $LASTEXITCODE
Write-Host "      Bitti (kod: $rc)"
if ($rc -ge 8) {
    Write-Host "HATA: Kopyalama basarisiz! Kod: $rc" -ForegroundColor Red
    exit 1
}

# Boş log/cache klasörleri
@("storage\logs","storage\framework\views","storage\framework\cache\data",
  "storage\framework\sessions","bootstrap\cache") | ForEach-Object {
    $d = "$STAGING\kosar\$_"
    if (-not (Test-Path $d)) { New-Item -ItemType Directory $d -Force | Out-Null }
}

$count = (Get-ChildItem "$STAGING\kosar" -Recurse -File -ErrorAction SilentlyContinue).Count
Write-Host "      [OK] kosar\: $count dosya." -ForegroundColor Green

# === 4) public_html\ ===
Write-Host ""
Write-Host "[4/6] public_html hazirlaniyor..." -ForegroundColor Yellow

foreach ($dir in @("css","js","build","images")) {
    $src = "$ROOT\public\$dir"
    if (Test-Path $src) {
        & robocopy $src "$STAGING\public_html\$dir" /E /NP /NFL /NDL /NJH /NJS | Out-Null
    }
}
foreach ($f in @("favicon.svg","robots.txt")) {
    if (Test-Path "$ROOT\public\$f") { Copy-Item "$ROOT\public\$f" "$STAGING\public_html\" -Force }
}
Copy-Item "$ROOT\deploy\public_html\index.php" "$STAGING\public_html\" -Force
Copy-Item "$ROOT\deploy\public_html\.htaccess" "$STAGING\public_html\" -Force

$storagePublic = "$ROOT\storage\app\public"
if (Test-Path $storagePublic) {
    Write-Host "      public_html\storage gorselleri kopyalaniyor..." -ForegroundColor DarkYellow
    & robocopy $storagePublic "$STAGING\public_html\storage" /E /NP /NFL /NDL /NJH /NJS | Out-Null
    if ($LASTEXITCODE -ge 8) {
        Write-Host "HATA: public_html\storage kopyalanamadi! Kod: $LASTEXITCODE" -ForegroundColor Red
        exit 1
    }
}

Write-Host "      [OK] public_html\ hazir." -ForegroundColor Green

# === 5) .env + APP_KEY ===
Write-Host ""
Write-Host "[5/6] .env ve kurulum dosyalari..." -ForegroundColor Yellow

$envDst = "$STAGING\kosar\.env"
if (Test-Path "$ROOT\deploy\.env.canli.sqlite") {
    Copy-Item "$ROOT\deploy\.env.canli.sqlite" $envDst -Force
} else {
    "APP_NAME=Kosar`nAPP_ENV=production`nAPP_KEY=`nAPP_DEBUG=false`nAPP_URL=https://kosarticaret.com`nDB_CONNECTION=sqlite`nADMIN_PASSWORD=DEGISTIR" | Set-Content $envDst -Encoding UTF8
}

$keyLine = Get-Content "$ROOT\.env" -Encoding UTF8 -ErrorAction SilentlyContinue |
           Where-Object { $_ -match "^APP_KEY=.+" } | Select-Object -First 1
if ($keyLine) {
    $c = Get-Content $envDst -Encoding UTF8
    ($c -replace "^APP_KEY=.*", $keyLine) | Set-Content $envDst -Encoding UTF8
    Write-Host "      APP_KEY kopyalandi." -ForegroundColor Green
} else {
    Write-Host "      UYARI: APP_KEY bos." -ForegroundColor DarkYellow
}

if (Test-Path "$ROOT\deploy\sunucu-kurulum.sh") { Copy-Item "$ROOT\deploy\sunucu-kurulum.sh" $STAGING -Force }
if (Test-Path "$ROOT\deploy\KURULUM-CANLI.md")  { Copy-Item "$ROOT\deploy\KURULUM-CANLI.md"  "$STAGING\KURULUM.md" -Force }

Write-Host "      [OK] .env hazir." -ForegroundColor Green

# === 6) ZIP ===
Write-Host ""
Write-Host "[6/6] ZIP olusturuluyor (bekleyin)..." -ForegroundColor Yellow

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
try {
    $zipStream = [System.IO.File]::Open($ZIPFILE, [System.IO.FileMode]::Create)
    $zip = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)
    Get-ChildItem $STAGING -Recurse -File | ForEach-Object {
        $entryName = $_.FullName.Substring($STAGING.Length + 1).Replace('\', '/')
        $entry = $zip.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $fileStream = [System.IO.File]::OpenRead($_.FullName)
        $fileStream.CopyTo($entryStream)
        $fileStream.Dispose()
        $entryStream.Dispose()
    }
    $zip.Dispose()
    $zipStream.Dispose()
} catch {
    if ($zip) { $zip.Dispose() }
    if ($zipStream) { $zipStream.Dispose() }
    Write-Host "HATA: ZIP olusturulamadi: $_" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $ZIPFILE)) {
    Write-Host "HATA: ZIP olusturulamadi!" -ForegroundColor Red
    exit 1
}

$mb = [math]::Round((Get-Item $ZIPFILE).Length / 1MB)
Remove-Item $STAGING -Recurse -Force

Write-Host "      [OK] ~$mb MB" -ForegroundColor Green
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  PAKET HAZIR!" -ForegroundColor Green
Write-Host "  deploy\kosarticaret-canli-$TARIH.zip  (~$mb MB)" -ForegroundColor Green
Write-Host ""
Write-Host "  1. ZIP'i DirectAdmin'e yukle:"
Write-Host "     domains/kosarticaret.com/"
Write-Host "  2. Extract et"
Write-Host "  3. kosar\.env => ADMIN_PASSWORD degistir!"
Write-Host "  4. SSH: bash sunucu-kurulum.sh"
Write-Host "================================================" -ForegroundColor Green

Start-Process explorer "$ROOT\deploy"
