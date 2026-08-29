<?php

/**
 * Aktif kategorilerin description/faq/meta verisini JSON'a aktarir.
 * Calistir: php database/seeders/build_category_seo_export.php
 */
declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\Category::query()
    ->where('active', true)
    ->orderBy('id')
    ->get()
    ->map(function (App\Models\Category $category): array {
        $description = trim((string) $category->description);
        $faq = is_array($category->faq) ? $category->faq : [];

        if ($description === '' && $faq === []) {
            return [];
        }

        return array_filter([
            'id' => $category->id,
            'description' => $description,
            'faq' => $faq,
            'meta_title' => trim((string) $category->meta_title),
            'meta_description' => trim((string) $category->meta_description),
        ], fn ($value) => $value !== '' && $value !== []);
    })
    ->filter()
    ->values()
    ->all();

$out = dirname(__DIR__).'/seeders/category_seo_export.json';
file_put_contents(
    $out,
    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
);

echo 'Export: '.count($rows).' kategori -> '.$out.PHP_EOL;
