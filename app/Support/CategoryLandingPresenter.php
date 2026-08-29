<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;

final class CategoryLandingPresenter
{
    /** @return array{subtitle: ?string, buying_guide: ?string, faq: list<array{q: string, a: string}>, trust: list<array{icon: string, label: string}>} */
    public static function for(Category $category): array
    {
        $path = $category->nestedSlugPath();
        $landing = config("category_buying_guides.landings.{$path}", []);

        $buyingGuide = RichContent::normalize($category->buying_guide);
        if ($buyingGuide === null || trim(strip_tags($buyingGuide)) === '') {
            $buyingGuide = RichContent::normalize($landing['buying_guide'] ?? null);
        }

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
            'subtitle' => $subtitle !== '' ? $subtitle : null,
            'buying_guide' => $buyingGuide,
            'faq' => $faq,
            'trust' => is_array($trust) ? $trust : [],
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
