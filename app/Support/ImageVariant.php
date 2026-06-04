<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class ImageVariant
{
    /** @var array<string, array{w:int,h:int,mode:string,quality:int}> */
    private const SPECS = [
        'product-card' => ['w' => 480, 'h' => 480, 'mode' => 'contain', 'quality' => 82],
        'product-pdp' => ['w' => 1200, 'h' => 1200, 'mode' => 'contain', 'quality' => 84],
        'product-thumb' => ['w' => 160, 'h' => 160, 'mode' => 'contain', 'quality' => 78],
        'category-card' => ['w' => 720, 'h' => 405, 'mode' => 'cover', 'quality' => 82],
        'brand-logo' => ['w' => 360, 'h' => 144, 'mode' => 'contain-transparent', 'quality' => 82],
        'site-logo' => ['w' => 420, 'h' => 120, 'mode' => 'contain-transparent', 'quality' => 82],
        'banner' => ['w' => 1440, 'h' => 520, 'mode' => 'max', 'quality' => 84],
        'blog-card' => ['w' => 960, 'h' => 540, 'mode' => 'cover', 'quality' => 82],
    ];

    /** @return list<string> */
    public static function presetsFor(string $type): array
    {
        return match ($type) {
            'product' => ['product-card', 'product-pdp', 'product-thumb'],
            'product-gallery' => ['product-pdp', 'product-thumb'],
            'category' => ['category-card'],
            'brand' => ['brand-logo'],
            'site-logo' => ['site-logo'],
            'banner' => ['banner'],
            'blog' => ['blog-card'],
            default => [],
        };
    }

    public static function url(?string $path, ?string $variant = null): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if ($variant !== null) {
            $variantPath = self::path($path, $variant);
            if ($variantPath !== null && Storage::disk('public')->exists($variantPath)) {
                return asset('storage/'.$variantPath);
            }
        }

        return asset('storage/'.$path);
    }

    public static function srcset(?string $path, array $variants): ?string
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        $items = [];
        foreach ($variants as $variant => $width) {
            $variantPath = self::path($path, (string) $variant);
            if ($variantPath !== null && Storage::disk('public')->exists($variantPath)) {
                $items[] = asset('storage/'.$variantPath).' '.(int) $width.'w';
            }
        }

        return $items !== [] ? implode(', ', $items) : null;
    }

    public static function generate(?string $path, array $variants): void
    {
        if (! self::canProcess($path)) {
            return;
        }

        foreach ($variants as $variant) {
            self::generateOne((string) $path, (string) $variant);
        }
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $dir = trim(dirname($path), '.\\/');
        $name = pathinfo($path, PATHINFO_FILENAME);
        $prefix = ($dir !== '' ? $dir.'/' : '').'_optimized/'.$name.'-';

        foreach (array_keys(self::SPECS) as $variant) {
            Storage::disk('public')->delete($prefix.$variant.'.webp');
        }
    }

    public static function path(string $path, string $variant): ?string
    {
        if (! isset(self::SPECS[$variant])) {
            return null;
        }

        $dir = trim(dirname($path), '.\\/');
        $name = pathinfo($path, PATHINFO_FILENAME);

        return ($dir !== '' ? $dir.'/' : '').'_optimized/'.$name.'-'.$variant.'.webp';
    }

    private static function canProcess(?string $path): bool
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        if (! function_exists('imagewebp') || ! Storage::disk('public')->exists($path)) {
            return false;
        }

        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private static function generateOne(string $path, string $variant): void
    {
        $spec = self::SPECS[$variant] ?? null;
        $target = self::path($path, $variant);
        if ($spec === null || $target === null) {
            return;
        }

        $sourcePath = Storage::disk('public')->path($path);
        $source = self::load($sourcePath);
        if (! $source) {
            return;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return;
        }

        [$canvas, $dstX, $dstY, $dstW, $dstH, $srcX, $srcY, $cropW, $cropH] = self::canvas($source, $srcW, $srcH, $spec);

        imagecopyresampled($canvas, $source, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH);

        $targetPath = Storage::disk('public')->path($target);
        $targetDir = dirname($targetPath);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        imagewebp($canvas, $targetPath, $spec['quality']);

        imagedestroy($source);
        imagedestroy($canvas);
    }

    /** @return resource|\GdImage|false */
    private static function load(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $image = match ($ext) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if ($image && function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }

        return $image;
    }

    /**
     * @param  resource|\GdImage  $source
     * @param  array{w:int,h:int,mode:string,quality:int}  $spec
     * @return array{0:resource|\GdImage,1:int,2:int,3:int,4:int,5:int,6:int,7:int,8:int}
     */
    private static function canvas($source, int $srcW, int $srcH, array $spec): array
    {
        $targetW = $spec['w'];
        $targetH = $spec['h'];
        $mode = $spec['mode'];

        if ($mode === 'max') {
            $scale = min(1, $targetW / $srcW, $targetH / $srcH);
            $targetW = max(1, (int) round($srcW * $scale));
            $targetH = max(1, (int) round($srcH * $scale));
            $mode = 'contain';
        }

        $transparent = str_contains($mode, 'transparent');
        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($transparent) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $fill = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        } else {
            $fill = imagecolorallocate($canvas, 255, 255, 255);
        }
        imagefill($canvas, 0, 0, $fill);

        if ($mode === 'cover') {
            $scale = max($targetW / $srcW, $targetH / $srcH);
            $cropW = (int) round($targetW / $scale);
            $cropH = (int) round($targetH / $scale);
            $srcX = max(0, (int) floor(($srcW - $cropW) / 2));
            $srcY = max(0, (int) floor(($srcH - $cropH) / 2));

            return [$canvas, 0, 0, $targetW, $targetH, $srcX, $srcY, min($srcW, $cropW), min($srcH, $cropH)];
        }

        $scale = min($targetW / $srcW, $targetH / $srcH);
        $dstW = max(1, (int) round($srcW * $scale));
        $dstH = max(1, (int) round($srcH * $scale));
        $dstX = (int) floor(($targetW - $dstW) / 2);
        $dstY = (int) floor(($targetH - $dstH) / 2);

        return [$canvas, $dstX, $dstY, $dstW, $dstH, 0, 0, $srcW, $srcH];
    }
}
