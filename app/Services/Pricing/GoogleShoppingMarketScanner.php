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
        $product->loadMissing('brand');
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
                    'last_error' => 'Google Shopping’da güvenilir teklif yok (model kodu / fiyat bandı filtresi). Yanlış düşük fiyatlar elendi.',
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
        $sku = trim((string) $product->sku);
        $brand = trim((string) ($product->brand?->name ?? ''));
        $name = trim((string) $product->name);

        // Model SKU varsa kısa ve net ara — uzun SEO başlığı yanlış ürün karıştırıyor.
        if ($sku !== '' && preg_match('/[A-Za-z].*\d|\d.*[A-Za-z]/', $sku)) {
            $query = trim($brand !== '' ? $brand.' '.$sku : $sku);
            if (mb_strlen($query) >= 4) {
                return Str::limit(preg_replace('/\s+/u', ' ', $query) ?? $query, 200, '');
            }
        }

        $parts = array_filter([
            $name,
            $sku !== '' ? $sku : null,
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
        $minBand = $ourPrice * (float) config('services.dataforseo.price_band_min', 0.55);
        $maxBand = $ourPrice * (float) config('services.dataforseo.price_band_max', 2.25);
        $minScore = (float) config('services.dataforseo.min_match_score', 0.34);
        $modelKeys = $this->modelKeys($product);

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
            if ($modelKeys !== [] && ! $this->titleHasModelKeys($title, $modelKeys)) {
                continue;
            }

            $score = $this->matchScore($product, $title, $modelKeys);
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

        if ($offers === []) {
            return [];
        }

        // Google bazen doğru ürün için bayat/çok düşük fiyat basıyor; medyanın %75 altını at.
        $prices = collect($offers)->pluck('price')->map(fn ($p) => (float) $p)->sort()->values();
        $median = (float) $prices->get((int) floor(($prices->count() - 1) / 2));
        $floor = $median * (float) config('services.dataforseo.outlier_floor', 0.75);
        $offers = array_values(array_filter($offers, fn (array $o) => (float) $o['price'] >= $floor));

        usort($offers, function (array $a, array $b) {
            if ($a['score'] === $b['score']) {
                return $a['price'] <=> $b['price'];
            }

            return $b['score'] <=> $a['score'];
        });

        // Skora göre sırala, sonra fiyat için yeniden min hesaplanır; ilk 15'i tut.
        usort($offers, fn (array $a, array $b) => $a['price'] <=> $b['price']);

        return array_slice($offers, 0, 15);
    }

    /**
     * Model kodu anahtarları (ör. 2CP-32-200B → 2cp + 200b).
     *
     * @return list<string>
     */
    public function modelKeys(Product $product): array
    {
        $raw = trim((string) $product->sku);
        if ($raw === '') {
            if (preg_match('/\b([A-Z]{1,6}\s*\d+[A-Z0-9\/\-]*)\b/i', (string) $product->name, $m)) {
                $raw = $m[1];
            }
        }

        if ($raw === '') {
            return [];
        }

        $normalized = Str::lower(Str::ascii($raw));
        $normalized = str_replace(['/', ' '], '-', $normalized);
        $parts = preg_split('/[-_]+/', $normalized) ?: [];

        $keys = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 2) {
                continue;
            }
            // Saf sayısal kısa parçalar (32) tek başına yetersiz; harf içerenleri tut.
            if (preg_match('/[a-z]/', $part) || mb_strlen($part) >= 4) {
                $keys[] = $part;
            }
        }

        // 2CP + 200B gibi en az iki güçlü anahtar tercih et
        $keys = array_values(array_unique($keys));

        return array_slice($keys, 0, 4);
    }

    /** @param  list<string>  $keys */
    private function titleHasModelKeys(string $title, array $keys): bool
    {
        $hay = Str::lower(Str::ascii($title));
        $hay = str_replace(['/', ' '], '-', $hay);

        $hits = 0;
        foreach ($keys as $key) {
            if ($key !== '' && str_contains($hay, $key)) {
                $hits++;
            }
        }

        // Tek anahtar varsa zorunlu; birden fazlaysa en az 2 (veya hepsi ≤2 ise hepsi)
        if (count($keys) === 1) {
            return $hits >= 1;
        }

        return $hits >= min(2, count($keys));
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

    /** @param  list<string>  $modelKeys */
    private function matchScore(Product $product, string $title, array $modelKeys = []): float
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

        foreach ($modelKeys as $key) {
            if ($key !== '' && str_contains(Str::lower(Str::ascii($title)), $key)) {
                $boost += 0.15;
            }
        }

        foreach ($nameTokens as $token) {
            if (preg_match('/[a-z].*\d|\d.*[a-z]/i', $token) && in_array($token, $titleTokens, true)) {
                $boost += 0.12;
            }
        }

        return round(min(1.0, ($jaccard * 0.7) + $boost), 3);
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text) ?? '';
        $parts = preg_split('/\s+/', trim($text)) ?: [];

        $stop = ['ve', 'ile', 'icin', 'icin', 'the', 'and', 'of', 'pompa', 'urun', 'urun', 'cift', 'fanli', 'santrafuj'];

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
