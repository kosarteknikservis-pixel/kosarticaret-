<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Str;

function plain(string $value): string
{
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value) ?: '';
    return trim(str_replace([' ,', ','], [',', ', '], $value));
}

function clipWords(string $value, int $max): string
{
    $value = plain($value);
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

    return $out !== '' ? $out : mb_substr($value, 0, $max);
}

function inferBrand(string $name, ?string $brand): string
{
    if (filled($brand)) {
        return trim($brand);
    }

    $lower = Str::lower($name);
    foreach ([
        'pedrollo' => 'Pedrollo',
        'sumak' => 'Sumak',
        'kaysu' => 'Kaysu Pompa',
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

function inferType(string $name, array $categoryNames): string
{
    $haystack = Str::lower($name.' '.implode(' ', $categoryNames));

    $rules = [
        'bıçaklı dalgıç pompa' => ['bıçaklı', 'bicakli', 'öğütücülü', 'ogutuculu', 'cut'],
        'foseptik dalgıç pompa' => ['foseptik', 'fosseptik', 'wqd', 'pissu'],
        'drenaj dalgıç pompa' => ['drenaj', 'spauto', 'flatör', 'flator'],
        'derin kuyu dalgıç pompa' => ['derin kuyu', '4sr', '6sr', 'qdx'],
        'temiz su dalgıç pompa' => ['temiz su', 'dalgıç temiz', 'sp750', 'sp400'],
        'dalgıç pompa' => ['dalgıç', 'dalgic'],
        'hidrofor sistemi' => ['hidrofor', 'hidromat'],
        'jet pompa' => ['jet', 'derinden emişli'],
        'preferikal sürtme fanlı pompa' => ['preferikal', 'sürtme fanlı', 'surtme fanli', 'hqb', 'ps-'],
        'tek fanlı santrifüj pompa' => ['tek fanlı', 'tek fanli', 'hcpf'],
        'çift fanlı santrifüj pompa' => ['çift fanlı', 'cift fanli', '2hcp'],
        'kademeli pompa' => ['kademeli', 'çok kademeli', 'cok kademeli', 'hmc'],
        'sirkülasyon pompası' => ['sirkülasyon', 'sirkulasyon'],
        'sanayi tipi vantilatör' => ['vantilatör', 'vantilator', 'ksv'],
        'motor koruma ve elektrik ekipmanı' => ['kontaktör', 'kontaktor', 'şalter', 'salter', 'sigorta', 'röle', 'role'],
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

function modelCode(string $name): ?string
{
    preg_match('/\b[A-ZÇĞİÖŞÜ0-9]{2,}[A-ZÇĞİÖŞÜ0-9\/\.\-]*\b/u', $name, $matches);
    return $matches[0] ?? null;
}

function metaTitle(string $name): string
{
    $suffix = ' | Koşar Ticaret';
    $baseMax = 60 - mb_strlen($suffix);
    return clipWords($name, $baseMax).$suffix;
}

function metaDescription(string $name, string $brand, string $type): string
{
    $shortName = clipWords($name, 62);
    $text = "{$shortName} {$type} için güncel fiyat, teknik özellik ve stok bilgisi. {$brand} güvencesiyle hızlı teslimat ve Koşar Ticaret teknik desteği.";

    if (mb_strlen($text) <= 165) {
        return $text;
    }

    $text = "{$shortName} {$type}: güncel fiyat, teknik özellik ve stok bilgisi. Garantili alışveriş, hızlı teslimat ve Koşar Ticaret teknik desteği.";
    if (mb_strlen($text) <= 165) {
        return $text;
    }

    return clipWords($text, 160);
}

function imageAlt(string $name, string $brand, string $type): string
{
    $model = modelCode($name);
    $modelPart = $model ? " {$model}" : '';
    $alt = "{$brand}{$modelPart} {$type} ürün görseli";

    return clipWords($alt, 120);
}

function tagsFor(string $name, string $brand, string $type, array $categoryNames): array
{
    $tags = [
        Str::lower($brand),
        Str::lower($type),
        Str::lower($name),
    ];

    if ($model = modelCode($name)) {
        $tags[] = Str::lower($model);
    }

    foreach ($categoryNames as $categoryName) {
        $tags[] = Str::lower($categoryName);
    }

    foreach ([
        'pompa' => 'su pompası',
        'dalgıç' => 'dalgıç pompa',
        'hidrofor' => 'hidrofor fiyatları',
        'vantilatör' => 'sanayi tipi vantilatör',
        'sirkülasyon' => 'sirkülasyon pompası',
        'kademeli' => 'kademeli pompa',
        'foseptik' => 'foseptik pompası',
        'drenaj' => 'drenaj pompası',
    ] as $needle => $keyword) {
        if (Str::contains(Str::lower($name.' '.$type), $needle)) {
            $tags[] = $keyword;
        }
    }

    $clean = [];
    foreach ($tags as $tag) {
        $tag = trim(preg_replace('/\s+/u', ' ', plain($tag)) ?: '');
        $tag = trim($tag, " \t\n\r\0\x0B,.-");
        if ($tag !== '' && mb_strlen($tag) <= 80) {
            $clean[] = $tag;
        }
    }

    return array_values(array_unique(array_slice($clean, 0, 10)));
}

$stats = [
    'products' => 0,
    'image_alt' => 0,
    'meta_title' => 0,
    'meta_description' => 0,
    'tags' => 0,
    'gallery_alt' => 0,
];

Product::query()
    ->with(['brand', 'categories', 'images'])
    ->orderBy('id')
    ->chunkById(200, function ($products) use (&$stats) {
        foreach ($products as $product) {
            $stats['products']++;
            $name = plain((string) $product->name);
            $categoryNames = $product->categories->pluck('name')->filter()->values()->all();
            $brand = inferBrand($name, $product->brand?->name);
            $type = inferType($name, $categoryNames);

            $updates = [];

            if (! filled($product->image_alt)) {
                $updates['image_alt'] = imageAlt($name, $brand, $type);
                $stats['image_alt']++;
            }

            if (! filled($product->meta_title)) {
                $updates['meta_title'] = metaTitle($name);
                $stats['meta_title']++;
            }

            if (! filled($product->meta_description)) {
                $updates['meta_description'] = metaDescription($name, $brand, $type);
                $stats['meta_description']++;
            }

            if (empty($product->tags)) {
                $updates['tags'] = tagsFor($name, $brand, $type, $categoryNames);
                $stats['tags']++;
            }

            if ($updates !== []) {
                $product->forceFill($updates)->save();
            }

            foreach ($product->images as $index => $image) {
                if (! filled($image->alt)) {
                    $image->forceFill([
                        'alt' => imageAlt($name, $brand, $type).' '.($index + 2),
                    ])->save();
                    $stats['gallery_alt']++;
                }
            }
        }
    });

echo "Product SEO fill completed\n";
foreach ($stats as $key => $value) {
    echo "{$key}: {$value}\n";
}
