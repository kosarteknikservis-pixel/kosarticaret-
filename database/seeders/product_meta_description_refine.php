<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Str;

function p_plain(string $value): string
{
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value) ?: '';
    return trim(str_replace([' ,', ','], [',', ', '], $value));
}

function p_clipWords(string $value, int $max): string
{
    $value = p_plain($value);
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

function p_inferBrand(string $name, ?string $brand): string
{
    if (filled($brand)) {
        return trim($brand);
    }

    $lower = Str::lower($name);
    foreach ([
        'pedrollo' => 'Pedrollo',
        'sumak' => 'Sumak',
        'kaysu' => 'Kaysu',
        'winpo' => 'Winpo',
        'horoz' => 'Horoz Electric',
        'koşar' => 'Koşar Ticaret',
        'kosar' => 'Koşar Ticaret',
    ] as $needle => $label) {
        if (Str::contains($lower, $needle)) {
            return $label;
        }
    }

    return 'Koşar Ticaret';
}

function p_inferType(string $name, array $categoryNames): string
{
    $haystack = Str::lower($name.' '.implode(' ', $categoryNames));
    $rules = [
        'bıçaklı dalgıç pompa' => ['bıçaklı', 'bicakli', 'öğütücülü', 'ogutuculu', 'cut'],
        'foseptik dalgıç pompa' => ['foseptik', 'wqd', 'pissu'],
        'drenaj dalgıç pompa' => ['drenaj', 'spauto', 'flatör', 'flator'],
        'derin kuyu dalgıç pompa' => ['derin kuyu', '4sr', '6sr', 'qdx'],
        'temiz su dalgıç pompa' => ['temiz su', 'dalgıç temiz'],
        'dalgıç pompa' => ['dalgıç', 'dalgic'],
        'hidrofor' => ['hidrofor', 'hidromat'],
        'jet pompa' => ['jet', 'derinden emişli'],
        'preferikal pompa' => ['preferikal', 'sürtme fanlı', 'surtme fanli', 'hqb', 'ps-'],
        'tek fanlı santrifüj pompa' => ['tek fanlı', 'hcpf'],
        'çift fanlı santrifüj pompa' => ['çift fanlı', '2hcp'],
        'kademeli pompa' => ['kademeli', 'çok kademeli', 'hmc'],
        'sirkülasyon pompası' => ['sirkülasyon', 'sirkulasyon'],
        'sanayi tipi vantilatör' => ['vantilatör', 'vantilator', 'ksv'],
        'elektrik ekipmanı' => ['kontaktör', 'şalter', 'sigorta', 'röle'],
        'su pompası' => ['pompa'],
    ];

    foreach ($rules as $label => $needles) {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, $needle)) {
                return $label;
            }
        }
    }

    return $categoryNames[0] ?? 'teknik ürün';
}

function p_metaDescription(string $name, string $brand, string $type): string
{
    $shortName = p_clipWords($name, 55);
    $brandPart = $brand === 'Koşar Ticaret' ? '' : "{$brand} ";

    $text = "{$shortName} {$type} için fiyat, stok ve teknik özellikleri inceleyin. {$brandPart}modellerde garantili alışveriş, hızlı teslimat ve teknik destek.";

    if (mb_strlen($text) <= 165) {
        return $text;
    }

    $shortName = p_clipWords($name, 45);
    $text = "{$shortName} {$type}: fiyat, stok ve teknik özellikler. Garantili alışveriş, hızlı teslimat ve Koşar Ticaret teknik desteği.";

    if (mb_strlen($text) <= 165) {
        return $text;
    }

    return "{$shortName} {$type}: fiyat, stok ve teknik özellikler. Garantili alışveriş ve hızlı teslimat.";
}

$updated = 0;

Product::query()
    ->with(['brand', 'categories'])
    ->orderBy('id')
    ->chunkById(200, function ($products) use (&$updated) {
        foreach ($products as $product) {
            $name = p_plain((string) $product->name);
            $categoryNames = $product->categories->pluck('name')->filter()->values()->all();
            $brand = p_inferBrand($name, $product->brand?->name);
            $type = p_inferType($name, $categoryNames);

            $product->forceFill([
                'meta_description' => p_metaDescription($name, $brand, $type),
            ])->save();
            $updated++;
        }
    });

echo "Refined product meta descriptions: {$updated}\n";
