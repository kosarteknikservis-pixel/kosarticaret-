#!/bin/bash
# kosarticaret.com — SSH kurulum (migrate YOK, veritabani hazir gelir)

set -e

PHP=/usr/local/php83/bin/php
BASE=/home/admin/domains/kosarticaret.com

cd "$BASE/kosar"

chmod 664 database/database.sqlite
chmod -R 775 storage bootstrap/cache database

$PHP artisan storage:link

cd "$BASE"
rm -rf public_html/storage
ln -s ../kosar/storage/app/public public_html/storage

cd "$BASE/kosar"
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo ""
echo "Kurulum tamam."
echo "Test: https://kosarticaret.com"
echo "Panel: https://kosarticaret.com/yonetim/giris"
