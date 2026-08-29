<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CategoryCoverImageGenerator
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    public function generate(string $slug, string $title): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $font = $this->fontPath();
        if ($font === null) {
            return null;
        }

        $title = trim($title);
        if ($title === '') {
            return null;
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($image === false) {
            return null;
        }

        $background = imagecolorallocate($image, 244, 246, 248);
        $accent = imagecolorallocate($image, 30, 58, 95);
        $textColor = imagecolorallocate($image, 15, 23, 42);
        $muted = imagecolorallocate($image, 100, 116, 139);

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $background);
        imagefilledrectangle($image, 0, 0, self::WIDTH, 8, $accent);

        $lines = $this->wrapTitle($title, $font, 48, self::WIDTH - 160);
        $lineHeight = 62;
        $blockHeight = count($lines) * $lineHeight;
        $startY = (int) ((self::HEIGHT - $blockHeight) / 2) + 36;

        foreach ($lines as $index => $line) {
            $box = imagettfbbox(48, 0, $font, $line);
            $textWidth = abs($box[2] - $box[0]);
            $x = (int) ((self::WIDTH - $textWidth) / 2);
            $y = $startY + ($index * $lineHeight);
            imagettftext($image, 48, 0, $x, $y, $textColor, $font, $line);
        }

        $brand = 'kosarticaret.com';
        $brandBox = imagettfbbox(20, 0, $font, $brand);
        $brandWidth = abs($brandBox[2] - $brandBox[0]);
        imagettftext($image, 20, 0, self::WIDTH - $brandWidth - 48, self::HEIGHT - 40, $muted, $font, $brand);

        Storage::disk('public')->makeDirectory('categories/covers');

        $safeSlug = Str::slug($slug, '-', 'tr');
        $relativePath = 'categories/covers/'.$safeSlug.'.jpg';
        $absolutePath = Storage::disk('public')->path($relativePath);

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }

        $saved = imagejpeg($image, $absolutePath, 88);
        imagedestroy($image);

        if (! $saved) {
            return null;
        }

        ImageVariant::optimizeOriginal($relativePath, 'category');
        ImageVariant::generate($relativePath, ImageVariant::presetsFor('category'));

        return $relativePath;
    }

    /** @return list<string> */
    private function wrapTitle(string $title, string $font, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', $title) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($fontSize, 0, $font, $candidate);
            $width = abs($box[2] - $box[0]);

            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    private function fontPath(): ?string
    {
        foreach ($this->fontCandidates() as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function fontCandidates(): array
    {
        return [
            resource_path('fonts/DejaVuSans-Bold.ttf'),
            storage_path('app/fonts/DejaVuSans-Bold.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
        ];
    }
}
