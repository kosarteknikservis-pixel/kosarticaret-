<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class RichContent
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'a', 'blockquote',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
    ];

    /** @var list<string> */
    private const REMOVE_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript',
    ];

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
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $wrapper = $document->getElementsByTagName('div')->item(0);
        if (! $wrapper) {
            return '';
        }

        self::sanitizeNode($wrapper);

        $output = '';
        foreach ($wrapper->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if (! $child) {
                continue;
            }

            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::unwrap($child);
                self::sanitizeNode($node);

                return;
            }

            self::sanitizeAttributes($child);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $names = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $names[] = $attribute->name;
        }

        if ($tag === 'a') {
            $href = trim($element->getAttribute('href'));
            foreach ($names as $name) {
                $element->removeAttribute($name);
            }
            if ($href !== '' && (preg_match('#^https?://#i', $href) === 1 || str_starts_with($href, '/'))) {
                $element->setAttribute('href', $href);
                $element->setAttribute('rel', 'noopener noreferrer');
            }

            return;
        }

        $allowed = ['class', 'style'];
        if (in_array($tag, ['h2', 'h3', 'h4'], true)) {
            $allowed[] = 'id';
        }
        if (in_array($tag, ['th', 'td'], true)) {
            $allowed[] = 'colspan';
            $allowed[] = 'rowspan';
        }

        foreach ($names as $name) {
            $lname = strtolower($name);
            $value = $element->getAttribute($name);

            if (str_starts_with($lname, 'on') || ! in_array($lname, $allowed, true)) {
                $element->removeAttribute($name);
                continue;
            }

            if ($lname === 'class') {
                if ($value === '' || preg_match('/^[A-Za-z0-9 _-]+$/', $value) !== 1) {
                    $element->removeAttribute($name);
                }
                continue;
            }

            if ($lname === 'style') {
                $style = self::sanitizeStyleValue($value);
                if ($style === '') {
                    $element->removeAttribute($name);
                } else {
                    $element->setAttribute('style', $style);
                }
                continue;
            }

            if ($lname === 'id') {
                if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
                    $element->removeAttribute($name);
                }
                continue;
            }

            if ($lname === 'colspan' || $lname === 'rowspan') {
                $trimmed = trim($value);
                if (preg_match('/^\d+$/', $trimmed) !== 1) {
                    $element->removeAttribute($name);
                    continue;
                }
                $n = (int) $trimmed;
                if ($n < 1 || $n > 99) {
                    $element->removeAttribute($name);
                } else {
                    $element->setAttribute($lname, (string) $n);
                }
            }
        }
    }

    private static function sanitizeStyleValue(string $style): string
    {
        if (mb_strlen($style) > 500) {
            return '';
        }

        $lower = strtolower($style);
        foreach (['expression', 'javascript:', 'vbscript:', 'url(', '@import', 'behavior', '-moz-binding'] as $needle) {
            if (str_contains($lower, $needle)) {
                return '';
            }
        }

        return $style;
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
