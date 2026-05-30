@echo off
chcp 65001 >nul
title Kosar - Yerel gelistirme modu
cd /d "%~dp0"

echo.
echo Yerel vendor (test araclari) geri yukleniyor...
echo.

where composer >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    composer install --no-interaction
) else if exist "C:\xampp\php\php.exe" (
    C:\xampp\php\php.exe composer.phar install --no-interaction
) else (
    echo HATA: composer bulunamadi.
    pause
    exit /b 1
)

echo.
echo Tamam. Yerelde http://127.0.0.1:8001 ile calisabilirsiniz.
pause
