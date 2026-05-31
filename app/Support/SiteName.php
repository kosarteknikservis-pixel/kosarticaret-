<?php

namespace App\Support;

use App\Models\SiteSetting;

class SiteName
{
    public static function get(): string
    {
        $name = (string) SiteSetting::get('site_name', config('kosar.name'));

        return self::normalize($name);
    }

    /**
     * Eski "Kosar" yazımı ve .env UTF-8 bozulması (ör. KoÅar → Koşar).
     */
    public static function normalize(string $name): string
    {
        $name = trim($name);

        if ($name === '' || strcasecmp($name, 'Kosar') === 0 || strcasecmp($name, 'KOSAR') === 0) {
            return 'Koşar';
        }

        if (preg_match('/[ÅÃÄâ€Ÿ]/u', $name)) {
            $fixed = @mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1');
            if (is_string($fixed) && $fixed !== '' && mb_check_encoding($fixed, 'UTF-8')) {
                $name = $fixed;
            }
            if (preg_match('/^Ko[sşÅ].*ar$/iu', $name)) {
                return 'Koşar';
            }
        }

        return $name;
    }
}
