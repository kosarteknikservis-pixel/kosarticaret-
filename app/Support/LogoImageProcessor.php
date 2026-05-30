<?php

namespace App\Support;

class LogoImageProcessor
{
    /**
     * Sadece saf beyaza çok yakın pikselleri şeffaf yapar.
     * Mavi/koyu logo renklerine dokunmaz.
     */
    public static function stripLightBackground(string $absolutePath, $whiteThreshold = 252): bool
    {
        if (! is_file($absolutePath) || ! function_exists('imagecreatefrompng')) {
            return false;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            return false;
        }

        $source = @imagecreatefrompng($absolutePath);
        if ($source === false) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        imagealphablending($source, false);
        imagesavealpha($source, true);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($source, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                if ($r >= $whiteThreshold && $g >= $whiteThreshold && $b >= $whiteThreshold) {
                    $transparent = imagecolorallocatealpha($source, 255, 255, 255, 127);
                    imagesetpixel($source, $x, $y, $transparent);
                }
            }
        }

        $saved = imagepng($source, $absolutePath, 6);
        imagedestroy($source);

        return $saved;
    }
}
