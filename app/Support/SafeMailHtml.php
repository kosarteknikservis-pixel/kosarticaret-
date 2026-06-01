<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class SafeMailHtml
{
    /** @var array<int, string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'span', 'div',
        'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'img',
        'table', 'thead', 'tbody', 'tr', 'td', 'th', 'blockquote', 'hr',
    ];

    /** @var array<int, string> */
    private const ALLOWED_ATTRS = [
        'href', 'src', 'alt', 'title', 'style', 'width', 'height',
        'align', 'colspan', 'rowspan', 'cellpadding', 'cellspacing', 'border',
    ];

    /** @var array<int, string> */
    private const DANGEROUS_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'button', 'textarea', 'select', 'option', 'meta', 'link', 'base',
    ];

    public static function render(string $content, bool $isHtml): string
    {
        if (! $isHtml) {
            return nl2br(e($content));
        }

        return self::sanitize($content);
    }

    public static function sanitize(string $html): string
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

        self::sanitizeNode($document);

        $wrapper = $document->getElementsByTagName('div')->item(0);

        if (! $wrapper) {
            return '';
        }

        $output = '';
        foreach ($wrapper->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);

            if (! $child) {
                continue;
            }

            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DANGEROUS_TAGS, true)) {
                    $node->removeChild($child);
                    continue;
                }

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    self::unwrap($child);
                    continue;
                }

                self::sanitizeAttributes($child);
            }

            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        for ($i = $element->attributes->length - 1; $i >= 0; $i--) {
            $attribute = $element->attributes->item($i);

            if (! $attribute) {
                continue;
            }

            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (str_starts_with($name, 'on') || ! in_array($name, self::ALLOWED_ATTRS, true)) {
                $element->removeAttribute($attribute->name);
                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($value)) {
                $element->removeAttribute($attribute->name);
                continue;
            }

            if ($name === 'style') {
                $style = self::sanitizeStyle($value);
                $style === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $style);
            }
        }
    }

    private static function isSafeUrl(string $value): bool
    {
        $value = Str::lower($value);

        return $value === ''
            || str_starts_with($value, 'https://')
            || str_starts_with($value, 'http://')
            || str_starts_with($value, 'mailto:')
            || str_starts_with($value, '/');
    }

    private static function sanitizeStyle(string $style): string
    {
        $blocked = ['expression', 'javascript:', 'url(', '@import', 'behavior'];

        foreach ($blocked as $needle) {
            if (str_contains(Str::lower($style), $needle)) {
                return '';
            }
        }

        return mb_substr($style, 0, 1000);
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
