<?php

namespace App\Services\Seo;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SeoDriftScanner
{
    /**
     * @param  list<array{key: string, path: string}>  $urls
     * @return array{scanned_at: string, base_url: string, pages: list<array<string, mixed>>}
     */
    public function scan(string $baseUrl, array $urls, int $timeoutSeconds = 20): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $pages = [];

        foreach ($urls as $target) {
            $path = $target['path'];
            $url = $baseUrl.'/'.ltrim($path, '/');

            try {
                $pages[] = array_merge(
                    ['key' => $target['key'], 'url' => $url],
                    $this->inspectUrl($url, $timeoutSeconds)
                );
            } catch (\Throwable $e) {
                $pages[] = [
                    'key' => $target['key'],
                    'url' => $url,
                    'status_code' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'scanned_at' => now()->toIso8601String(),
            'base_url' => $baseUrl,
            'pages' => $pages,
        ];
    }

    /**
     * @param  array{scanned_at: string, base_url: string, pages: list<array<string, mixed>>}  $baseline
     * @param  array{scanned_at: string, base_url: string, pages: list<array<string, mixed>>}  $current
     * @return list<array{key: string, url: string, changes: list<array{field: string, before: mixed, after: mixed}>}>
     */
    public function diff(array $baseline, array $current): array
    {
        $baselineByKey = collect($baseline['pages'] ?? [])->keyBy('key');
        $regressions = [];

        foreach ($current['pages'] ?? [] as $page) {
            $key = (string) ($page['key'] ?? '');
            if ($key === '' || ! $baselineByKey->has($key)) {
                continue;
            }

            $before = $baselineByKey->get($key);
            $changes = $this->diffPage($before, $page);

            if ($changes !== []) {
                $regressions[] = [
                    'key' => $key,
                    'url' => $page['url'] ?? '',
                    'changes' => $changes,
                ];
            }
        }

        return $regressions;
    }

    /** @return array<string, mixed> */
    private function inspectUrl(string $url, int $timeoutSeconds): array
    {
        $response = Http::timeout($timeoutSeconds)
            ->withHeaders(['User-Agent' => 'KosarSeoDrift/1.0'])
            ->get($url);

        $status = $response->status();
        $body = (string) $response->body();
        $contentType = strtolower((string) $response->header('Content-Type'));

        if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'text/plain') && ! str_contains($contentType, 'xml')) {
            return [
                'status_code' => $status,
                'content_type' => $contentType,
            ];
        }

        if (str_contains($contentType, 'xml') || str_ends_with(parse_url($url, PHP_URL_PATH) ?: '', '.txt')) {
            return [
                'status_code' => $status,
                'content_type' => $contentType,
                'body_preview' => mb_substr(trim($body), 0, 500),
                'body_length' => strlen($body),
            ];
        }

        return array_merge(
            ['status_code' => $status],
            $this->parseHtmlSignals($body)
        );
    }

    /** @return array<string, mixed> */
    private function parseHtmlSignals(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $title = trim($xpath->evaluate('string(//title)'));
        $metaDescription = $this->metaContent($xpath, 'description');
        $canonical = $this->linkHref($xpath, 'canonical');
        $robots = $this->metaContent($xpath, 'robots');

        $h1Nodes = $xpath->query('//h1');
        $h1Texts = [];
        if ($h1Nodes !== false) {
            foreach ($h1Nodes as $node) {
                $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
                if ($text !== '') {
                    $h1Texts[] = $text;
                }
            }
        }

        return [
            'title' => $title,
            'meta_description' => $metaDescription,
            'canonical' => $canonical,
            'robots' => $robots,
            'h1_count' => count($h1Texts),
            'h1' => $h1Texts[0] ?? '',
            'json_ld_types' => $this->jsonLdTypes($html),
        ];
    }

    private function metaContent(DOMXPath $xpath, string $name): string
    {
        $value = $xpath->evaluate("string(//meta[@name='{$name}']/@content)");

        return trim((string) $value);
    }

    private function linkHref(DOMXPath $xpath, string $rel): string
    {
        $value = $xpath->evaluate("string(//link[@rel='{$rel}']/@href)");

        return trim((string) $value);
    }

    /** @return list<string> */
    private function jsonLdTypes(string $html): array
    {
        if (! preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return [];
        }

        $types = [];

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($decoded)) {
                continue;
            }

            foreach ($this->collectSchemaTypes($decoded) as $type) {
                $types[] = $type;
            }
        }

        $types = array_values(array_unique($types));
        sort($types);

        return $types;
    }

    /** @return list<string> */
    private function collectSchemaTypes(array $node): array
    {
        $types = [];

        if (isset($node['@type'])) {
            $type = $node['@type'];
            if (is_string($type)) {
                $types[] = $type;
            } elseif (is_array($type)) {
                foreach ($type as $item) {
                    if (is_string($item)) {
                        $types[] = $item;
                    }
                }
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $types = array_merge($types, $this->collectSchemaTypes($item));
                        }
                    }
                } else {
                    $types = array_merge($types, $this->collectSchemaTypes($value));
                }
            }
        }

        return $types;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<array{field: string, before: mixed, after: mixed}>
     */
    private function diffPage(array $before, array $after): array
    {
        $fields = ['status_code', 'title', 'meta_description', 'canonical', 'robots', 'h1_count', 'h1', 'json_ld_types'];
        $changes = [];

        foreach ($fields as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($this->normalizeValue($old) === $this->normalizeValue($new)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'before' => $old,
                'after' => $new,
            ];
        }

        return $changes;
    }

    private function normalizeValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return trim((string) $value);
    }

    public static function resolveBaseUrl(?string $override = null): string
    {
        $candidates = [
            $override,
            config('kosar.pagespeed.audit_base_url'),
            config('kosar.url'),
            config('app.url'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = rtrim(trim((string) $candidate), '/');
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Gecerli base URL bulunamadi. --base-url=https://kosarticaret.com kullanin.');
    }
}
