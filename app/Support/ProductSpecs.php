<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Vitrin ve schema için teknik özellik satırları: başlık satırlarını atar, eş anlamlı adları
 * config/product_spec_labels.php ile tek ada indirir. Ham `specs` verisi değişmez.
 */
class ProductSpecs
{
    /** @var array<string, string>|null */
    private static ?array $aliases = null;

    /**
     * @param  array<int|string, mixed>|null  $specs
     * @return Collection<int, array{0: string, 1: string}>
     */
    public static function rows(?array $specs): Collection
    {
        $rows = [];
        $used = [];

        foreach ($specs ?? [] as $key => $value) {
            $label = is_string($key) ? $key : (is_array($value) ? ($value['label'] ?? '') : '');
            $text = is_string($key) ? $value : (is_array($value) ? ($value['value'] ?? '') : '');

            $label = self::squish(is_scalar($label) ? (string) $label : '');
            $text = self::squish(is_scalar($text) ? (string) $text : '');

            if ($label === '' || $text === '' || self::isHeaderRow($label, $text)) {
                continue;
            }

            $display = self::displayLabel($label);
            $slot = self::normalize($display);

            if (isset($used[$slot])) {
                if (self::normalize($used[$slot]) === self::normalize($text)) {
                    continue;
                }

                $display = $label;
                $slot = self::normalize($label);
                if (isset($used[$slot])) {
                    continue;
                }
            }

            $used[$slot] = $text;
            $rows[] = [$display, $text];
        }

        return collect($rows);
    }

    public static function displayLabel(string $label): string
    {
        return self::aliases()[self::normalize($label)] ?? $label;
    }

    public static function isHeaderRow(string $label, string $value): bool
    {
        $label = self::normalize($label);
        $value = self::normalize($value);

        if (in_array($label, config('product_spec_labels.header_labels', []), true)
            && in_array($value, config('product_spec_labels.header_values', []), true)) {
            return true;
        }

        if (! in_array($label, config('product_spec_labels.column_header_labels', []), true)) {
            return false;
        }

        return isset(self::aliases()[$value])
            || in_array($value, config('product_spec_labels.column_header_values', []), true);
    }

    public static function normalize(string $text): string
    {
        // "İ" küçültülünce i + U+0307 olur; ham anahtarlarda bu biçim de var.
        return str_replace("\u{0307}", '', mb_strtolower(self::squish($text), 'UTF-8'));
    }

    private static function squish(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** @return array<string, string> */
    private static function aliases(): array
    {
        if (self::$aliases === null) {
            self::$aliases = [];
            foreach (config('product_spec_labels.aliases', []) as $raw => $display) {
                self::$aliases[self::normalize((string) $raw)] = (string) $display;
            }
        }

        return self::$aliases;
    }
}
