<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;

final class CategoryLandingPresenter
{
    /**
     * @return array{
     *   h1: ?string,
     *   subtitle: ?string,
     *   buying_guide: ?string,
     *   faq: list<array{q: string, a: string}>,
     *   trust: list<array{icon: string, label: string}>,
     *   geo: ?array,
     *   need_intents: ?array{label: ?string, items: list<array{label: string, href: string}>, exclude_slugs: list<string>}
     * }
     */
    public static function for(Category $category): array
    {
        $path = $category->nestedSlugPath();
        $landing = config("category_buying_guides.landings.{$path}", []);

        $buyingGuide = RichContent::normalize($category->buying_guide);
        if ($buyingGuide === null || trim(strip_tags($buyingGuide)) === '') {
            $buyingGuide = RichContent::normalize($landing['buying_guide'] ?? null);
        }

        $h1 = trim((string) ($landing['h1'] ?? ''));

        $subtitle = trim((string) ($landing['subtitle'] ?? ''));
        if ($subtitle === '') {
            $subtitle = self::subtitleFromCategory($category);
        }

        $faq = self::normalizeFaq($category->faq ?? []);
        if ($faq === []) {
            $faq = self::normalizeFaq($landing['faq'] ?? []);
        }

        /** @var list<array{icon: string, label: string}> $trust */
        $trust = $landing['trust'] ?? config('category_buying_guides.default_trust', []);

        return [
            'h1' => $h1 !== '' ? $h1 : null,
            'subtitle' => $subtitle !== '' ? $subtitle : null,
            'buying_guide' => $buyingGuide,
            'faq' => $faq,
            'trust' => is_array($trust) ? $trust : [],
            'geo' => GeoPageBlocks::forCategory($path),
            'need_intents' => self::needIntentsForPath($path),
        ];
    }

    /**
     * @return ?array{label: ?string, items: list<array{label: string, href: string}>, exclude_slugs: list<string>}
     */
    private static function needIntentsForPath(string $path): ?array
    {
        $config = config("category_need_intents.{$path}");
        if (! is_array($config) || empty($config['items']) || ! is_array($config['items'])) {
            return null;
        }

        $items = [];
        foreach ($config['items'] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $href = trim((string) ($item['href'] ?? ''));
            if ($label === '' || $href === '' || ! str_starts_with($href, '/')) {
                continue;
            }
            $items[] = ['label' => $label, 'href' => $href];
        }

        if ($items === []) {
            return null;
        }

        $exclude = [];
        foreach (($config['exclude_slugs'] ?? []) as $slug) {
            $slug = trim((string) $slug);
            if ($slug !== '') {
                $exclude[] = $slug;
            }
        }

        return [
            'label' => filled($config['label'] ?? null) ? trim((string) $config['label']) : null,
            'items' => $items,
            'exclude_slugs' => $exclude,
        ];
    }

    /** @param  mixed  $items
     * @return list<array{q: string, a: string}>
     */
    private static function normalizeFaq(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $question = trim(strip_tags((string) ($item['q'] ?? '')));
            $answer = trim((string) ($item['a'] ?? ''));
            if ($question === '' || trim(strip_tags($answer)) === '') {
                continue;
            }

            $normalized[] = ['q' => $question, 'a' => $answer];
        }

        return $normalized;
    }

    private static function subtitleFromCategory(Category $category): string
    {
        $candidates = [
            $category->meta_description,
            RichContent::plainText($category->description ?? ''),
        ];

        foreach ($candidates as $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }

            $sentence = Str::before($text, '.').'.';
            if (strlen($sentence) >= 40 && strlen($sentence) <= 200) {
                return $sentence;
            }

            return Str::limit($text, 180);
        }

        return '';
    }
}
