<?php

namespace App\Support;

/**
 * UTF-8 metnin Windows-1252/1254 sanılıp tekrar UTF-8'e çevrilmesiyle oluşan
 * "sÃ¼resi kaÃ§ yÄ±l" bozulmasını geri alır. Metin bu dönüşümle kayıpsız geri
 * çevrilemiyorsa (gerçek Türkçe karakter, ₺ vb. içeriyorsa) olduğu gibi bırakılır.
 */
final class Utf8Mojibake
{
    public static function repair(?string $text): ?string
    {
        if ($text === null || ! preg_match('/[ÃÄÅ][\x{0080}-\x{00BF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}-\x{2122}]/u', $text)) {
            return $text;
        }

        $bytes = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        if (mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252') !== $text || ! mb_check_encoding($bytes, 'UTF-8')) {
            return $text;
        }

        return $bytes;
    }
}
