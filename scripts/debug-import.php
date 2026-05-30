<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $stats = app(\App\Services\Catalog\WooCommerceCatalogImporter::class)->import(
        base_path('final_woocommerce_seo_import.xlsx'),
        fresh: true,
        limit: 1,
        downloadImages: false,
    );
    print_r($stats);
} catch (Throwable $e) {
    echo $e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString();
}
