<?php

namespace App\Services\Seo;

use DOMDocument;
use DOMNode;
use DOMText;

final class BlogCategoryLinkInjector
{
    /**
     * @return array{body: string, added: list<string>}
     */
    public function inject(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return ['body' => $html, 'added' => []];
        }

        $maxLinks = max(1, (int) config('blog_category_links.max_links_per_post', 3));
        $added = [];
        $linkedUrls = $this->extractLinkedPaths($html);

        libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="blog-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $document->getElementById('blog-root');
        if ($root === null) {
            return ['body' => $html, 'added' => []];
        }

        foreach (config('blog_category_links.rules', []) as $rule) {
            if (count($added) >= $maxLinks) {
                break;
            }

            $url = (string) ($rule['url'] ?? '');
            if ($url === '' || in_array($url, $linkedUrls, true)) {
                continue;
            }

            foreach ((array) ($rule['patterns'] ?? []) as $pattern) {
                if (! is_string($pattern) || trim($pattern) === '') {
                    continue;
                }

                if ($this->injectFirstMatch($root, trim($pattern), $url, $document)) {
                    $added[] = $url;
                    $linkedUrls[] = $url;
                    break;
                }
            }
        }

        $body = '';
        foreach ($root->childNodes as $child) {
            $body .= $document->saveHTML($child);
        }

        return ['body' => $body, 'added' => $added];
    }

    /** @return list<string> */
    private function extractLinkedPaths(string $html): array
    {
        preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches);

        return array_values(array_unique(array_map(
            static fn (string $href): string => parse_url($href, PHP_URL_PATH) ?: $href,
            $matches[1] ?? []
        )));
    }

    private function injectFirstMatch(DOMNode $node, string $keyword, string $url, DOMDocument $document): bool
    {
        if (strtolower($node->nodeName) === 'a') {
            return false;
        }

        if ($node instanceof DOMText) {
            $text = $node->textContent ?? '';
            $position = mb_stripos($text, $keyword, 0, 'UTF-8');
            if ($position === false) {
                return false;
            }

            $before = mb_substr($text, 0, $position, 'UTF-8');
            $match = mb_substr($text, $position, mb_strlen($keyword, 'UTF-8'), 'UTF-8');
            $after = mb_substr($text, $position + mb_strlen($keyword, 'UTF-8'), null, 'UTF-8');

            $anchor = $document->createElement('a');
            $anchor->setAttribute('href', $url);
            $anchor->appendChild($document->createTextNode($match));

            $parent = $node->parentNode;
            if ($parent === null) {
                return false;
            }

            if ($before !== '') {
                $parent->insertBefore($document->createTextNode($before), $node);
            }

            $parent->insertBefore($anchor, $node);

            if ($after !== '') {
                $node->textContent = $after;
            } else {
                $parent->removeChild($node);
            }

            return true;
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($this->injectFirstMatch($child, $keyword, $url, $document)) {
                return true;
            }
        }

        return false;
    }
}
