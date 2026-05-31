<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class SiteLogo
{
    public static function path(): ?string
    {
        $preview = session('preview_settings');
        if (is_array($preview) && array_key_exists('site_logo', $preview)) {
            $path = $preview['site_logo'];

            return $path !== '' && $path !== null ? (string) $path : null;
        }

        $path = SiteSetting::get('site_logo');

        return $path !== '' && $path !== null ? $path : null;
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

    public static function alt(): string
    {
        return SiteName::get();
    }

    public static function has(): bool
    {
        return self::url() !== null;
    }

    public static function deleteStored(): void
    {
        $path = SiteSetting::get('site_logo');
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
        SiteSetting::set('site_logo', null);
    }
}
