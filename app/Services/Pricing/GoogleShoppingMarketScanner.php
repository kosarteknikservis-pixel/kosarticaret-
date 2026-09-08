<?php

namespace App\Services\Pricing;

use App\Models\MarketPriceScan;
use App\Models\Product;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleShoppingMarketScanner
{
    private const OWN_SELLERS = [
        'kosar',
        'koşar',
        'kosarticaret',
        'koşar ticaret',
    ];

    public function __construct(private DataForSeoClient $client) {}

    public function scanProduct(Product $product): MarketPriceScan
    {
        $query = $this->buildQuery($product);
        $scan = MarketPriceScan::query()->firstOrNew(['product_id' => $product->id]);
        $scan->search_query = $query;

        try {
            $rawItems = $this->fetchShoppingItems($query);
            $offers = $this->normalizeOffers($rawItems, $product);

            if ($offers === []) {
                $scan->fill([
                    'status' => MarketPriceScan::STATUS_NO_RESULTS,
                    'google_min_price' => null,
                    'google_median_price' => null,
                    'offer_count' => 0,
                    'offers' => [],
                    'last_scanned_at' => now(),
                    'last_error' => 'Google Shopping’da uygun teklif bulunamadı (eşleşme eşiği / fiyat bandı).',
                ])->save();

                return $scan->fresh();
            }

            $prices = collect($offers)->pluck('price')->map(fn ($p) => (float) $p)->sort()->values();
            $min = round((float) $prices->first(), 2);
            $median = round((float) $prices->get((int) floor(($prices->count() - 1) / 2)), 2);

            $scan->fill([
                'status' => MarketPriceScan::STATUS_PENDING,
                'google_min_price' => $min,
                'google_median_price' => $median,
                'offer_count' => count($offers),
                'offers' => $offers,
                'last_scanned_at' => now(),
                'last_error' => null,
            ])->save();

            return $scan->fresh();
        } catch (Throwable $e) {
            $scan->fill([
                'status' => MarketPriceScan::STATUS_ERROR,
                'last_scanned_at' => now(),
                'last_error' => Str::limit($e->getMessage(), 500, ''),
            ])->save();

            return $scan->fresh();
        }
    }

    public function buildQuery(Product $product): string
    {
        $parts = array_filter([
            trim((string) $product->name),
            filled($product->sku) ? trim((string) $product->sku) : null,
            filled($product->barcode) ? trim((string) $product->barcode) : null,
        ]);

        $query = trim(implode(' ', $parts));
        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;

        return Str::limit($query, 200, '');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchShoppingItems(string $keyword): array
    {
        $posted = $this->client->postMerchantProductsTask([
            'keyword' => $keyword,
            'location_code' => (int) config('services.dataforseo.location_code', 2792),
            'language_code' => (string) config('services.dataforseo.language_code', 'tr'),
            'depth' => (int) config('services.dataforseo.shopping_depth', 40),
            'priority' => 2,
        ]);

        $taskId = (string) $posted['id'];
        $deadline = now()->addSeconds((int) config('services.dataforseo.poll_timeout', 75));
        $interval = max(0, (int) config('services.dataforseo.poll_interval', 3));

        while (now()->lt($deadline)) {
            if ($interval > 0) {
                sleep($interval);
            }
            $response = $this->client->getMerchantProductsTask($taskId);
            $task = $response['tasks'][0] ?? null;
            if (! is_array($task)) {
                continue;
            }

            $code = (int) ($task['status_code'] ?? 0);
            if ($code === 20000) {
                $items = $task['result'][0]['items'] ?? [];

                return is_array($items) ? $items : [];
            }

            // 40601/40602 = still in queue / crawling
            if ($code >= 40000 && ! in_array($code, [40601, 40602], true)) {
                throw new RuntimeException((string) ($task['status_message'] ?? 'Shopping görevi başarısız.'));
            }
        }

        throw new RuntimeException('Google Shopping sonuçları zaman aşımına uğradı.');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{title: string, price: float, seller: string, url: ?string, product_id: ?string, score: float}>
     */
    private function normalizeOffers(array $items, Product $product): array
    {
        $ourPrice = max(0.01, (float) $product->price);
        $minBand = $ourPrice * (float) config('services.dataforseo.price_band_min', 0.35);
        $maxBand = $ourPrice * (float) config('services.dataforseo.price_band_max', 2.75);
        $minScore = (float) config('services.dataforseo.min_match_score', 0.28);

        $offers = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['type'] ?? '') !== 'google_shopping_serp') {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $price = $this->normalizePrice($item['price'] ?? null);
            $seller = trim((string) ($item['seller'] ?? ''));

            if ($title === '' || $price === null || $price <= 0) {
                continue;
            }
            if ($this->isOwnSeller($seller)) {
                continue;
            }
            if ($price < $minBand || $price > $maxBand) {
                continue;
            }

            $score = $this->matchScore($product, $title);
            if ($score < $minScore) {
                continue;
            }

            $offers[] = [
                'title' => Str::limit($title, 220, ''),
                'price' => $price,
                'seller' => Str::limit($seller !== '' ? $seller : 'Bilinmiyor', 120, ''),
                'url' => isset($item['shopping_url']) ? (string) $item['shopping_url'] : null,
                'product_id' => isset($item['product_id']) ? (string) $item['product_id'] : null,
                'score' => $score,
            ];
        }

        usort($offers, function (array $a, array $b) {
            if ($a['price'] === $b['price']) {
                return $b['score'] <=> $a['score'];
            }

            return $a['price'] <=> $b['price'];
        });

        return array_slice($offers, 0, 15);
    }

    private function normalizePrice(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            $value = round((float) $raw, 2);

            return $value > 0 ? $value : null;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $raw = trim(str_replace(["\xc2\xa0", ' '], '', $raw));
        $raw = preg_replace('/[^\d.,]/', '', $raw) ?? '';

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $raw) || preg_match('/^\d+(,\d{1,2})$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (substr_count($raw, ',') === 1 && substr_count($raw, '.') === 0) {
            $raw = str_replace(',', '.', $raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $value = round((float) $raw, 2);

        return $value > 0 ? $value : null;
    }

    private function isOwnSeller(string $seller): bool
    {
        $normalized = Str::lower(Str::ascii($seller));

        foreach (self::OWN_SELLERS as $own) {
            if ($normalized !== '' && str_contains($normalized, Str::lower(Str::ascii($own)))) {
                return true;
            }
        }

        return false;
    }

    private function matchScore(Product $product, string $title): float
    {
        $titleTokens = $this->tokens($title);
        $nameTokens = $this->tokens((string) $product->name);

        if ($titleTokens === [] || $nameTokens === []) {
            return 0.0;
        }

        $overlap = count(array_intersect($nameTokens, $titleTokens));
        $jaccard = $overlap / max(1, count(array_unique(array_merge($nameTokens, $titleTokens))));

        $boost = 0.0;
        foreach ([$product->sku, $product->barcode] as $code) {
            $code = trim((string) $code);
            if ($code !== '' && mb_strlen($code) >= 3 && Str::contains(Str::lower($title), Str::lower($code))) {
                $boost += 0.25;
            }
        }

        // Model-like tokens (digits + letters) heavily weighted
        foreach ($nameTokens as $token) {
            if (preg_match('/[a-z].*\d|\d.*[a-z]/i', $token) && in_array($token, $titleTokens, true)) {
                $boost += 0.12;
            }
        }

        return round(min(1.0, ($jaccard * 0.75) + $boost), 3);
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text) ?? '';
        $parts = preg_split('/\s+/', trim($text)) ?: [];

        $stop = ['ve', 'ile', 'icin', 'için', 'the', 'and', 'of', 'pompa', 'urun', 'ürün'];

        $tokens = [];
        foreach ($parts as $part) {
            if (mb_strlen($part) < 2) {
                continue;
            }
            if (in_array($part, $stop, true)) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }
}
