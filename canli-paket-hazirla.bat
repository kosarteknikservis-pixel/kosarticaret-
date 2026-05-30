@echo off
chcp 65001 >nul
title Kosar - Canli paket hazirla
cd /d "%~dp0"

echo.
echo  ============================================
echo   CANLI PAKET (kosarticaret.com)
echo  ============================================
echo   Bu islem 2-5 dakika surebilir.
echo   Bittikten sonra yerel gelistirme otomatik acilir.
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\build-canli-paket.ps1"
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo HATA: Paket olusturulamadi.
    pause
    exit /b 1
)

echo.
echo deploy klasorundeki zip dosyasini DirectAdmin'e yukleyin.
echo.
explorer "%~dp0deploy"
pause
