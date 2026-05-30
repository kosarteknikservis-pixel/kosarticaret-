<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class SiteFavicon
{
    public static function path(): ?string
    {
        $preview = session('preview_settings');
        if (is_array($preview) && array_key_exists('site_favicon', $preview)) {
            $path = $preview['site_favicon'];

            return $path !== '' && $path !== null ? (string) $path : null;
        }

        $path = SiteSetting::get('site_favicon');

        return $path !== '' && $path !== null ? (string) $path : null;
    }

    public static function customUrl(): ?string
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

    public static function url(): string
    {
        return self::customUrl() ?? asset('favicon.svg');
    }

    public static function mime(): string
    {
        $url = self::url();

        if (str_contains($url, '.svg')) {
            return 'image/svg+xml';
        }

        if (str_contains($url, '.webp')) {
            return 'image/webp';
        }

        if (str_contains($url, '.ico')) {
            return 'image/x-icon';
        }

        return 'image/png';
    }

    public static function appleTouchUrl(): string
    {
        return self::customUrl() ?? self::url();
    }

    public static function hasCustom(): bool
    {
        return self::customUrl() !== null;
    }

    public static function deleteStored(): void
    {
        $path = SiteSetting::get('site_favicon');
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
        SiteSetting::set('site_favicon', null);
    }
}
