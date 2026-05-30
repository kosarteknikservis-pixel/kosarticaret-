<?php

namespace App\Support;

use App\Models\HomeBanner;

final class ImageUploadSpec
{
    /** @return array{key: string, width: int, height: int, ratio_label: string, hint: string, formats: string, max_kb: int, max_mb: float, safe_zone: ?string} */
    public static function get(string $key): array
    {
        if ($key === 'home_banner_slider') {
            return self::mergeHomeBannerSlider();
        }

        $raw = config("kosar.image_specs.{$key}");
        if (! is_array($raw)) {
            return self::defaults($key);
        }

        return self::normalize($key, $raw);
    }

    /** @return array{key: string, width: int, height: int, ratio_label: string, hint: string, formats: string, max_kb: int, max_mb: float, safe_zone: ?string} */
    public static function forBannerType(string $type): array
    {
        return match ($type) {
            HomeBanner::TYPE_SLIDER, HomeBanner::TYPE_BANNER => self::get('home_banner_slider'),
            HomeBanner::TYPE_PRODUCT => self::withNote(self::get('home_banner_tile'), 'Görsel yüklemezseniz ürün kapak görseli kullanılır ('.self::label('product_cover').').'),
            HomeBanner::TYPE_CATEGORY => self::withNote(self::get('home_banner_tile'), 'Görsel yüklemezseniz kategori görseli kullanılır ('.self::label('category').').'),
            HomeBanner::TYPE_PRODUCT_LIST => self::withNote(self::get('home_banner_tile'), 'Ürün listesi görsel kullanmaz; kaynak ve adet panelden seçilir.'),
            default => self::get('home_banner_tile'),
        };
    }

    /** @param  array<string, mixed>  $spec */
    private static function withNote(array $spec, string $extra): array
    {
        $spec['hint'] = trim(($spec['hint'] ?? '').' '.$extra);

        return $spec;
    }

    public static function label(string $key): string
    {
        $s = self::get($key);

        return $s['ratio_label'];
    }

    /** @return array{key: string, width: int, height: int, ratio_label: string, hint: string, formats: string, max_kb: int, max_mb: float, safe_zone: ?string} */
    private static function mergeHomeBannerSlider(): array
    {
        $hb = HomeBannerSpec::all();

        return self::normalize('home_banner_slider', [
            'width' => $hb['width'],
            'height' => $hb['height'],
            'hint' => 'Slider ve geniş banner satırları. Paneldeki “Slider ölçüsü” ile aynı oran.',
            'formats' => $hb['formats'],
            'max_kb' => $hb['max_kb'],
            'safe_zone' => 'Metin ve logo sol/orta güvenli alanda kalsın; kenarlar kırpılabilir.',
        ]);
    }

    /** @param  array<string, mixed>  $raw */
    private static function normalize(string $key, array $raw): array
    {
        $width = (int) ($raw['width'] ?? 800);
        $height = (int) ($raw['height'] ?? 600);
        $maxKb = (int) ($raw['max_kb'] ?? 2048);

        return [
            'key' => $key,
            'width' => $width,
            'height' => $height,
            'ratio_label' => $raw['ratio_label'] ?? "{$width} × {$height} px",
            'hint' => (string) ($raw['hint'] ?? 'Görseli bu ölçüde hazırlayın; farklı oranlar vitrinde kırpılır (sıkışmaz, taşmaz).'),
            'formats' => (string) ($raw['formats'] ?? 'JPG, PNG veya WebP'),
            'max_kb' => $maxKb,
            'max_mb' => round($maxKb / 1024, 1),
            'safe_zone' => $raw['safe_zone'] ?? null,
        ];
    }

    /** @return array{key: string, width: int, height: int, ratio_label: string, hint: string, formats: string, max_kb: int, max_mb: float, safe_zone: ?string} */
    private static function defaults(string $key): array
    {
        return self::normalize($key, ['width' => 800, 'height' => 800]);
    }
}
