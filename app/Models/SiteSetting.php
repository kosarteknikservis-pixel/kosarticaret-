<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $preview = session('preview_settings');
        if (is_array($preview) && array_key_exists($key, $preview)) {
            return (string) $preview[$key];
        }

        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
    }

    /** @return array<string, string> */
    public static function allCached(): array
    {
        return Cache::remember('settings.all', 300, function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }
}
