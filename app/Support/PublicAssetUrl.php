<?php

namespace App\Support;

final class PublicAssetUrl
{
    public static function resolve(?string $path, ?string $variant = null): ?string
    {
        return ImageVariant::url($path, $variant);
    }

    public static function srcset(?string $path, array $variants): ?string
    {
        return ImageVariant::srcset($path, $variants);
    }
}
