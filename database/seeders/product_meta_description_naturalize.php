<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Str;

function n_plain(string $value): string
{
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value) ?: '';
    return trim(str_replace([' ,', ','], [',', ', '], $value));
}

function n_clipWords(string $value, int $max): string
{
    $value = n_plain($value);
    if (mb_strlen($value) <= $max) {
        return $value;
    }

    $words = preg_split('/\s+/u', $value) ?: [];
    $out = '';
    foreach ($words as $word) {
        $candidate = trim($out.' '.$word);
        if (mb_strlen($candidate) > $max) {
            break;
        }
        $out = $candidate;
    }

    return $out ?: mb_substr($value, 0, $max);
}

function n_brand(string $name, ?string $brand): string
{
    if (filled($brand)) {
        return trim($brand);
    }

    $lower = Str::lower($name);
    foreach (['Pedrollo', 'Sumak', 'Kaysu', 'Winpo'] as $candidate) {
        if (Str::contains($lower, Str::lower($candidate))) {
            return $candidate;
        }
    }

    if (Str::contains($lower, ['koşar', 'kosar'])) {
        return 'Koşar Ticaret';
    }

    return '';
}

function n_description(string $name, ?string $brand): string
{
    $shortName = n_clipWords($name, 64);
    $brand = n_brand($name, $brand);

    if ($brand !== '' && ! Str::contains(Str::lower($shortName), Str::lower(str_replace(' Pompa', '', $brand)))) {
        $text = "{$shortName} için fiyat, stok ve teknik özellikleri inceleyin. {$brand} markalı üründe garantili alışveriş, hızlı teslimat ve teknik destek.";
    } else {
        $text = "{$shortName} için fiyat, stok ve teknik özellikleri inceleyin. Garantili alışveriş, hızlı teslimat ve Koşar Ticaret teknik desteği.";
    }

    if (mb_strlen($text) <= 165) {
        return $text;
    }

    $shortName = n_clipWords($name, 52);
    $text = "{$shortName} için fiyat, stok ve teknik özellikleri inceleyin. Garantili alışveriş, hızlı teslimat ve teknik destek.";

    return $text;
}

$updated = 0;
Product::query()
    ->with('brand')
    ->orderBy('id')
    ->chunkById(200, function ($products) use (&$updated) {
        foreach ($products as $product) {
            $product->forceFill([
                'meta_description' => n_description((string) $product->name, $product->brand?->name),
            ])->save();
            $updated++;
        }
    });

echo "Naturalized product meta descriptions: {$updated}\n";
