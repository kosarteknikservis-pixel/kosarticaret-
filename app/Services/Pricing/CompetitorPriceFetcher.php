<?php

namespace App\Services\Pricing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CompetitorPriceFetcher
{
    /**
     * @return array{ok: bool, price: ?float, title: ?string, error: ?string}
     */
    public function fetch(string $url): array
    {
        $url = trim($url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'price' => null, 'title' => null, 'error' => 'Geçersiz URL.'];
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return ['ok' => false, 'price' => null, 'title' => null, 'error' => 'URL host bulunamadı.'];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; KosarPriceBot/1.0; +https://kosarticaret.com)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'tr-TR,tr;q=0.9,en;q=0.8',
                ])
                ->get($url);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'price' => null,
                    'title' => null,
                    'error' => 'Sayfa alınamadı (HTTP '.$response->status().').',
                ];
            }

            $html = $response->body();
            $title = $this->extractTitle($html);
            $price = $this->extractPrice($html);

            if ($price === null || $price <= 0) {
                return [
                    'ok' => false,
                    'price' => null,
                    'title' => $title,
                    'error' => 'Fiyat HTML içinde bulunamadı. Elle girin veya JSON-LD/meta fiyatı olan sayfa kullanın.',
                ];
            }

            return ['ok' => true, 'price' => $price, 'title' => $title, 'error' => null];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'price' => null,
                'title' => null,
                'error' => 'Çekim hatası: '.$e->getMessage(),
            ];
        }
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return $title !== '' ? Str::limit($title, 180, '') : null;
        }

        return null;
    }

    private function extractPrice(string $html): ?float
    {
        foreach ($this->jsonLdPrices($html) as $price) {
            if ($price > 0) {
                return $price;
            }
        }

        $patterns = [
            '/itemprop=["\']price["\'][^>]*content=["\']([0-9]+(?:[.,][0-9]+)?)["\']/i',
            '/content=["\']([0-9]+(?:[.,][0-9]+)?)["\'][^>]*itemprop=["\']price["\']/i',
            '/property=["\']product:price:amount["\'][^>]*content=["\']([0-9]+(?:[.,][0-9]+)?)["\']/i',
            '/"price"\s*:\s*"?(?<![0-9])([0-9]{2,7}(?:[.,][0-9]{1,2})?)"?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $price = $this->normalizePrice($m[1]);
                if ($price !== null && $price > 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    /** @return list<float> */
    private function jsonLdPrices(string $html): array
    {
        $prices = [];
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $blocks)) {
            return $prices;
        }

        foreach ($blocks[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($decoded)) {
                continue;
            }
            $this->collectPricesFromJson($decoded, $prices);
        }

        return $prices;
    }

    /** @param  array<mixed>  $node */
    private function collectPricesFromJson(array $node, array &$prices): void
    {
        $type = $node['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];

        if (in_array('Product', $types, true) || in_array('Offer', $types, true) || in_array('AggregateOffer', $types, true)) {
            foreach (['price', 'lowPrice', 'highPrice'] as $key) {
                if (isset($node[$key])) {
                    $price = $this->normalizePrice((string) $node[$key]);
                    if ($price !== null) {
                        $prices[] = $price;
                    }
                }
            }
            if (isset($node['offers'])) {
                $offers = $node['offers'];
                if (isset($offers[0]) && is_array($offers)) {
                    foreach ($offers as $offer) {
                        if (is_array($offer)) {
                            $this->collectPricesFromJson($offer, $prices);
                        }
                    }
                } elseif (is_array($offers)) {
                    $this->collectPricesFromJson($offers, $prices);
                }
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $isList = array_is_list($value);
                if ($isList) {
                    foreach ($value as $child) {
                        if (is_array($child)) {
                            $this->collectPricesFromJson($child, $prices);
                        }
                    }
                } elseif (isset($value['@type']) || isset($value['offers']) || isset($value['price'])) {
                    $this->collectPricesFromJson($value, $prices);
                }
            }
        }
    }

    private function normalizePrice(string $raw): ?float
    {
        $raw = trim(str_replace(["\xc2\xa0", ' '], '', $raw));
        $raw = preg_replace('/[^\d.,]/', '', $raw) ?? '';

        if ($raw === '') {
            return null;
        }

        // 14.301,94 or 14301,94 (TR)
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $raw) || preg_match('/^\d+(,\d{1,2})$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (substr_count($raw, ',') === 1 && substr_count($raw, '.') === 0) {
            $raw = str_replace(',', '.', $raw);
        } elseif (substr_count($raw, '.') > 1) {
            $raw = str_replace('.', '', $raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $value = round((float) $raw, 2);

        return $value > 0 && $value < 10000000 ? $value : null;
    }
}
