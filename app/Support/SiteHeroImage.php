<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class SiteHeroImage
{
    public static function path(): ?string
    {
        $preview = session('preview_settings');
        if (is_array($preview) && array_key_exists('hero_image', $preview)) {
            $path = $preview['hero_image'];

            return $path !== '' && $path !== null ? (string) $path : null;
        }

        $path = SiteSetting::get('hero_image');

        return $path !== '' && $path !== null ? (string) $path : null;
    }

    public static function url(): ?string
    {
        $path = self::path();
        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public static function deleteStored(): void
    {
        $path = SiteSetting::get('hero_image');
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
        SiteSetting::set('hero_image', null);
    }
}
