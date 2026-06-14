<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LegacyProductSlugMatcher
{
    /** @var Collection<int, string>|null */
    private static ?Collection $activeSlugs = null;

    public static function targetForLegacySlug(string $rawSlug): ?string
    {
        $slug = self::normalizeSlug($rawSlug);

        if ($slug === '') {
            return null;
        }

        if (self::activeSlugs()->has($slug)) {
            return '/urun/'.$slug;
        }

        $exact = config('legacy_product_redirects', []);
        $key = '/urun/'.$slug;
        if (isset($exact[$key])) {
            return (string) $exact[$key];
        }

        $bestSlug = null;
        $bestScore = 0.0;

        foreach (self::activeSlugs()->keys() as $activeSlug) {
            $score = self::similarity($slug, (string) $activeSlug);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSlug = (string) $activeSlug;
            }
        }

        if ($bestSlug !== null && $bestScore >= 82.0) {
            return '/urun/'.$bestSlug;
        }

        return LegacyRemovedProductRedirect::targetForSlug($slug);
    }

    public static function normalizeSlug(string $slug): string
    {
        $slug = urldecode(trim($slug, '/'));
        $slug = Str::ascii($slug);
        $slug = str_replace(['m3/h', 'm3-h', 'm³/h', 'm³-h'], 'm3h', $slug);
        $slug = preg_replace('/m[^a-z0-9]?h/i', 'm3h', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9\-]/i', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;

        return trim(strtolower($slug), '-');
    }

    /** @return Collection<int, string> */
    private static function activeSlugs(): Collection
    {
        if (self::$activeSlugs !== null) {
            return self::$activeSlugs;
        }

        self::$activeSlugs = Product::query()
            ->active()
            ->pluck('slug', 'slug');

        return self::$activeSlugs;
    }

    private static function similarity(string $left, string $right): float
    {
        similar_text($left, $right, $percent);

        return (float) $percent;
    }
}
