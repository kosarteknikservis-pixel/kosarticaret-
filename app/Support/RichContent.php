<?php

namespace App\Support;

use Illuminate\Support\Str;

class RichContent
{
    private const ALLOWED_TAGS = '<p><br><h2><h3><h4><ul><ol><li><strong><b><em><i><a><blockquote>'
        .'<table><thead><tbody><tfoot><tr><th><td><caption>';

    public static function isHtml(?string $content): bool
    {
        if ($content === null || trim($content) === '') {
            return false;
        }

        return $content !== strip_tags($content);
    }

    public static function plainText(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    public static function excerpt(?string $content, int $limit = 160): string
    {
        return Str::limit(self::plainText($content), $limit);
    }

    public static function wordCount(?string $content): int
    {
        $text = self::plainText($content);
        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /** Kayıt öncesi: HTML ise temizle, düz metin ise trim. */
    public static function normalize(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $content = self::unescapeLiteralNewlines($content);

        return self::isHtml($content) ? self::sanitizeHtml($content) : $content;
    }

    /** Vitrin çıktısı — güvenli HTML veya satır sonlu düz metin. */
    public static function render(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        if (! self::isHtml($content)) {
            return nl2br(e($content), false);
        }

        return self::sanitizeHtml($content);
    }

    public static function hasHeading(?string $content): bool
    {
        if ($content === null) {
            return false;
        }

        return (bool) preg_match('/<h[2-4][^>]*>/i', $content);
    }

    /** WooCommerce export bazen satır sonlarını metin olarak "\\n" yazar. */
    public static function unescapeLiteralNewlines(string $content): string
    {
        return str_replace(['\\r\\n', '\\r', '\\n'], ["\n", "\n", "\n"], $content);
    }

    public static function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/<(script|style|iframe|object|embed)[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/ on\w+="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/ on\w+=\'[^\']*\'/i', '', $html) ?? $html;

        $html = preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>/iu',
            static function (array $m): string {
                $url = trim($m[3]);
                if ($url === '' || preg_match('/^\s*javascript:/i', $url)) {
                    return '<a>';
                }
                if (! preg_match('#^https?://#i', $url) && ! str_starts_with($url, '/')) {
                    return '<a>';
                }

                return '<a href="'.e($url).'" rel="noopener noreferrer">';
            },
            $html
        ) ?? $html;

        $html = preg_replace('/<a\s+>/i', '<a>', $html) ?? $html;

        return trim($html);
    }
}
