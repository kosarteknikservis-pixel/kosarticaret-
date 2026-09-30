<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LegacyProductSlugMatcher
{
    /** @var Collection<int, string>|null */
    private static ?Collection $activeSlugs = null;

    /** @var array<string, string>|null */
    private static ?array $normalizedExact = null;

    public static function targetForLegacySlug(string $rawSlug): ?string
    {
        $decoded = urldecode(trim($rawSlug, '/'));
        if (self::activeSlugs()->has($decoded)) {
            return '/urun/'.$decoded;
        }

        $slug = self::normalizeSlug($rawSlug);

        if ($slug === '') {
            return null;
        }

        if (self::activeSlugs()->has($slug)) {
            return '/urun/'.$slug;
        }

        $exactTarget = self::exactTarget($rawSlug, $slug);
        if ($exactTarget !== null) {
            return $exactTarget;
        }

        $tokenMatch = self::bestTokenMatch($slug);
        if ($tokenMatch !== null) {
            return '/urun/'.$tokenMatch;
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

        if ($bestSlug !== null && $bestScore >= 78.0) {
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

    /**
     * Config anahtarları ham (m³-h, smh200) yazılır; gelen slug normalizeSlug'dan geçtiği için
     * anahtarlar da aynı kuralla normalleştirilerek aranır.
     */
    private static function exactTarget(string $rawSlug, string $slug): ?string
    {
        $exact = config('legacy_product_redirects', []);

        foreach (['/urun/'.trim($rawSlug, '/'), '/urun/'.urldecode(trim($rawSlug, '/')), '/urun/'.$slug] as $key) {
            if (isset($exact[$key])) {
                return (string) $exact[$key];
            }
        }

        if (self::$normalizedExact === null) {
            self::$normalizedExact = [];
            foreach ($exact as $source => $target) {
                if (! str_starts_with((string) $source, '/urun/')) {
                    continue;
                }

                $normalized = self::normalizeSlug(substr((string) $source, 6));
                self::$normalizedExact[$normalized] ??= (string) $target;
            }
        }

        return self::$normalizedExact[$slug] ?? null;
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

    private static function bestTokenMatch(string $slug): ?string
    {
        $sourceTokens = self::significantTokens($slug);
        if (count($sourceTokens) < 3) {
            return null;
        }

        $bestSlug = null;
        $bestScore = 0.0;

        foreach (self::activeSlugs()->keys() as $activeSlug) {
            $targetTokens = self::significantTokens((string) $activeSlug);
            if ($targetTokens === []) {
                continue;
            }

            $overlap = count(array_intersect($sourceTokens, $targetTokens));
            $score = $overlap / max(count($sourceTokens), count($targetTokens));

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSlug = (string) $activeSlug;
            }
        }

        return $bestScore >= 0.72 ? $bestSlug : null;
    }

    /** @return list<string> */
    private static function significantTokens(string $slug): array
    {
        $stop = ['ve', 'ile', 'icin', 'the', 'hp', 'volt', 'monofaze', 'trifaze', '220', '380', 'serisi', 'komple'];

        return array_values(array_filter(
            explode('-', self::normalizeSlug($slug)),
            fn (string $token) => strlen($token) >= 2 && ! in_array($token, $stop, true) && ! is_numeric($token)
        ));
    }
}
