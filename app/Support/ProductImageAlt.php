<?php

namespace App\Support;

final class ProductImageAlt
{
    public static function generate(string $productName, ?string $brandName = null): string
    {
        $name = trim($productName);
        $brand = trim((string) $brandName);

        if ($brand !== '') {
            return "{$brand} {$name} ürün görseli";
        }

        return "{$name} ürün görseli";
    }

    public static function needsNormalization(?string $alt, string $productName): bool
    {
        $alt = trim((string) $alt);
        $name = trim($productName);

        if ($alt === '') {
            return true;
        }

        if (mb_strtolower($alt) === mb_strtolower($name)) {
            return true;
        }

        if (! str_contains(mb_strtolower($alt), 'ürün görseli')) {
            return true;
        }

        return false;
    }
}
