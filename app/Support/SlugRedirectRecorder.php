<?php

namespace App\Support;

use App\Models\SlugRedirect;

final class SlugRedirectRecorder
{
    public static function record(string $from, string $to, string $source): void
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === '' || $to === '' || $from === $to) {
            return;
        }

        SlugRedirect::query()->where('to_path', $from)->update(['to_path' => $to]);
        SlugRedirect::query()->where('from_path', $to)->delete();

        SlugRedirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'source' => $source],
        );
    }

    private static function normalize(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? '' : $path;
    }
}
