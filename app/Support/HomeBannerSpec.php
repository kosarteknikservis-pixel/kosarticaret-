<?php

namespace App\Support;

use App\Models\SiteSetting;

final class HomeBannerSpec
{
    public static function width(): int
    {
        return self::normalizeDimension(
            (int) SiteSetting::get('home_banner_width', config('kosar.home_banner.width')),
            (int) config('kosar.home_banner.width'),
            400,
            3840,
        );
    }

    public static function height(): int
    {
        return self::normalizeDimension(
            (int) SiteSetting::get('home_banner_height', config('kosar.home_banner.height')),
            (int) config('kosar.home_banner.height'),
            200,
            2160,
        );
    }

    /** @return array{width: int, height: int, ratio_label: string, aspect_ratio: string, max_kb: int, formats: string} */
    public static function all(): array
    {
        $width = self::width();
        $height = self::height();

        return [
            'width' => $width,
            'height' => $height,
            'ratio_label' => "{$width} × {$height} px",
            'aspect_ratio' => "{$width} / {$height}",
            'max_kb' => (int) config('kosar.home_banner.max_kb'),
            'formats' => (string) config('kosar.home_banner.formats'),
        ];
    }

    public static function save(int $width, int $height): void
    {
        SiteSetting::set('home_banner_width', (string) self::normalizeDimension($width, (int) config('kosar.home_banner.width'), 400, 3840));
        SiteSetting::set('home_banner_height', (string) self::normalizeDimension($height, (int) config('kosar.home_banner.height'), 200, 2160));
    }

    private static function normalizeDimension(int $value, int $default, int $min, int $max): int
    {
        if ($value < $min || $value > $max) {
            return $default;
        }

        return $value;
    }
}
