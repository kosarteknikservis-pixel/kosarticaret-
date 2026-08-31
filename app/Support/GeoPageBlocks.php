<?php

namespace App\Support;

final class GeoPageBlocks
{
    /** @return array<string, mixed>|null */
    public static function forCategory(string $nestedPath): ?array
    {
        return self::normalize(config("geo_page_blocks.categories.{$nestedPath}"));
    }

    /** @return array<string, mixed>|null */
    public static function forBrand(string $slug): ?array
    {
        return self::normalize(config("geo_page_blocks.brands.{$slug}"));
    }

    /** @return array<string, mixed>|null */
    public static function forBlog(string $slug): ?array
    {
        return self::normalize(config("geo_page_blocks.blog.{$slug}"));
    }

    /** @param  mixed  $block
     * @return array<string, mixed>|null
     */
    private static function normalize(mixed $block): ?array
    {
        if (! is_array($block) || trim((string) ($block['short_answer'] ?? '')) === '') {
            return null;
        }

        $normalized = [
            'short_answer' => trim((string) $block['short_answer']),
        ];

        $priceBand = $block['price_band'] ?? null;
        if (is_array($priceBand) && isset($priceBand['from'], $priceBand['to'])) {
            $normalized['price_band'] = [
                'from' => (int) $priceBand['from'],
                'to' => (int) $priceBand['to'],
                'currency' => (string) ($priceBand['currency'] ?? 'TRY'),
                'note' => trim((string) ($priceBand['note'] ?? '')),
            ];
        }

        $table = $block['selection_table'] ?? null;
        if (is_array($table) && ! empty($table['rows'])) {
            $normalized['selection_table'] = [
                'title' => trim((string) ($table['title'] ?? 'Hızlı seçim tablosu')),
                'headers' => array_values(array_filter((array) ($table['headers'] ?? []))),
                'rows' => collect($table['rows'])
                    ->filter(fn ($row) => is_array($row) && $row !== [])
                    ->values()
                    ->all(),
            ];
        }

        $guideCta = $block['guide_cta'] ?? null;
        if (is_array($guideCta) && filled($guideCta['url'] ?? null) && filled($guideCta['label'] ?? null)) {
            $normalized['guide_cta'] = [
                'label' => trim((string) $guideCta['label']),
                'url' => trim((string) $guideCta['url']),
            ];
        }

        return $normalized;
    }
}
