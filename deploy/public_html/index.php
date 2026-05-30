<?php

/**
 * DirectAdmin: Document Root = public_html iken Laravel girişi.
 * kosar klasörü public_html ile aynı seviyede olmalı:
 *   domains/kosarticaret.com/kosar/
 *   domains/kosarticaret.com/public_html/  (bu dosya)
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$kosarRoot = dirname(__DIR__).'/kosar';

if (! is_file($kosarRoot.'/vendor/autoload.php')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Kurulum hatası: kosar klasörü bulunamadı ('.$kosarRoot.').';
    exit;
}

if (is_file($maintenance = $kosarRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $kosarRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $kosarRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
