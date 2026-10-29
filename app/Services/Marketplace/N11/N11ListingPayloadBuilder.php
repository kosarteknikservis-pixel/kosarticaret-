<?php

namespace App\Services\Marketplace\N11;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class N11ListingPayloadBuilder
{
    public function __construct(
        private MarketplaceChannelPricing $pricing,
    ) {}

    /**
     * @return array{payload: array{integrator: string, skus: list<array<string, mixed>>}}
     */
    public function buildCreatePayload(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $priceOverride = null,
        ?N11ApiClient $client = null,
    ): array {
        $product->loadMissing(['brand', 'categories', 'images']);

        $categoryMapping = $this->resolveCategoryMapping($product);
        $brandMapping = $this->resolveBrandMapping($product);
        if ($categoryMapping === null) {
            throw new InvalidArgumentException('N11 kategori eşleştirmesi bulunamadı.');
        }
        if ($brandMapping === null) {
            throw new InvalidArgumentException('N11 marka eşleştirmesi bulunamadı.');
        }

        $sale = round($this->pricing->salePrice($product, $channel, $listing, $priceOverride), 2);
        if ($sale <= 0) {
            throw new InvalidArgumentException('N11 satış fiyatı 0 olamaz.');
        }

        $list = round($this->pricing->listPrice($product, $channel, $listing, $priceOverride), 2);
        if ($list < $sale) {
            $list = $sale;
        }

        $stock = $this->pricing->stockQuantity($product, $channel, $listing);
        if ($stock < 1) {
            throw new InvalidArgumentException(
                'N11 stok 0 gönderilemez (SKU '.(string) ($product->sku ?: $product->id).').'
            );
        }

        $shipmentTemplate = trim((string) $channel->setting(
            'shipment_template',
            config('marketplace.n11.default_shipment_template', '')
        ));
        if ($shipmentTemplate === '') {
            throw new InvalidArgumentException(
                'N11 kargo şablonu (shipment_template) eksik. Panel → N11 kanal ayarlarından şablon adını girin.'
            );
        }

        $integrator = trim((string) $channel->setting(
            'integrator',
            config('marketplace.n11.integrator', 'KosarTicaret')
        ));
        $preparingDay = max(1, (int) $channel->setting(
            'preparing_day',
            config('marketplace.n11.preparing_day', 2)
        ));

        $images = $this->collectImages($product);
        if ($images === []) {
            throw new InvalidArgumentException('N11 için en az bir HTTPS görsel gerekli.');
        }

        $description = $this->sanitizeDescription(
            (string) ($product->description ?: $product->short_description ?: $product->name)
        );
        if (mb_strlen(strip_tags($description)) < 40) {
            throw new InvalidArgumentException('N11 açıklama çok kısa (SEO/kalite).');
        }

        $stockCode = mb_substr(strtoupper(trim((string) ($product->sku ?: 'P'.$product->id))), 0, 255);
        $title = mb_substr(trim((string) $product->name), 0, 100);
        if ($title === '') {
            throw new InvalidArgumentException('N11 ürün başlığı boş.');
        }

        $categoryId = (int) $categoryMapping->external_category_id;
        $attributes = $this->buildAttributes(
            $product,
            $brandMapping,
            $categoryId,
            $client,
        );

        $vat = (int) round($product->vatRateValue());
        if (! in_array($vat, [0, 1, 10, 20], true)) {
            $vat = 20;
        }

        $sku = [
            'title' => $title,
            'description' => $description,
            'categoryId' => $categoryId,
            'currencyType' => 'TL',
            'productMainId' => $stockCode,
            'preparingDay' => $preparingDay,
            'shipmentTemplate' => $shipmentTemplate,
            'stockCode' => $stockCode,
            'quantity' => $stock,
            'images' => $images,
            'attributes' => $attributes,
            'salePrice' => $sale,
            'listPrice' => $list,
            'vatRate' => $vat,
        ];

        $ean = $this->resolveOptionalEan13($product);
        if ($ean !== null) {
            $sku['barcode'] = $ean;
        }

        return [
            'payload' => [
                'integrator' => $integrator,
                'skus' => [$sku],
            ],
        ];
    }

    /**
     * @return list<array{id: int, valueId: int|null, customValue: string|null}>
     */
    private function buildAttributes(
        Product $product,
        MarketplaceBrandMapping $brandMapping,
        int $categoryId,
        ?N11ApiClient $client,
    ): array {
        $brandName = (string) ($brandMapping->external_brand_name ?: $product->brand?->name ?: 'Diğer');
        $attributes = [];

        if ($client === null || $categoryId <= 0) {
            return [[
                'id' => 1,
                'valueId' => null,
                'customValue' => $brandName,
            ]];
        }

        $catalog = $client->getCategoryAttributes($categoryId);
        $rows = $catalog['categoryAttributes'] ?? [];
        if (! is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as $row) {
            if (! is_array($row) || ! ($row['isMandatory'] ?? false)) {
                continue;
            }

            $attrId = (int) ($row['attributeId'] ?? 0);
            $name = mb_strtolower((string) ($row['attributeName'] ?? ''));
            if ($attrId <= 0) {
                continue;
            }

            $isCustom = (bool) ($row['isCustomValue'] ?? true);
            $values = is_array($row['attributeValues'] ?? null) ? $row['attributeValues'] : [];

            if (str_contains($name, 'marka')) {
                $matched = $this->matchAttributeValue($values, $brandName);
                $attributes[] = $matched !== null
                    ? ['id' => $attrId, 'valueId' => $matched, 'customValue' => null]
                    : ['id' => $attrId, 'valueId' => null, 'customValue' => $brandName];
                continue;
            }

            if (str_contains($name, 'renk')) {
                $matched = $this->matchAttributeValue($values, ['Standart', 'Çok Renkli', 'Diğer', 'Siyah']);
                if ($matched !== null) {
                    $attributes[] = ['id' => $attrId, 'valueId' => $matched, 'customValue' => null];
                } elseif ($isCustom) {
                    $attributes[] = ['id' => $attrId, 'valueId' => null, 'customValue' => 'Standart'];
                } else {
                    throw new InvalidArgumentException('N11 zorunlu renk özelliği eşleştirilemedi.');
                }
                continue;
            }

            if (str_contains($name, 'garanti')) {
                $matched = $this->matchAttributeValue($values, ['24', '2 Yıl', '2 yıl', '24 Ay', '24 ay']);
                if ($matched !== null) {
                    $attributes[] = ['id' => $attrId, 'valueId' => $matched, 'customValue' => null];
                } elseif ($isCustom) {
                    $attributes[] = ['id' => $attrId, 'valueId' => null, 'customValue' => '24'];
                }
                continue;
            }

            if (str_contains($name, 'mense') || str_contains($name, 'menşei') || str_contains($name, 'üretil')) {
                $matched = $this->matchAttributeValue($values, ['Türkiye', 'Turkiye', 'TR']);
                if ($matched !== null) {
                    $attributes[] = ['id' => $attrId, 'valueId' => $matched, 'customValue' => null];
                } elseif ($isCustom) {
                    $attributes[] = ['id' => $attrId, 'valueId' => null, 'customValue' => 'Türkiye'];
                }
                continue;
            }

            // Diğer zorunlular: listedeki ilk değer veya custom
            if (! $isCustom && $values !== []) {
                $firstId = (int) ($values[0]['id'] ?? 0);
                if ($firstId > 0) {
                    $attributes[] = ['id' => $attrId, 'valueId' => $firstId, 'customValue' => null];
                    continue;
                }
            }

            if ($isCustom) {
                $attributes[] = [
                    'id' => $attrId,
                    'valueId' => null,
                    'customValue' => $this->fallbackCustomValue($name, $product),
                ];
                continue;
            }

            throw new InvalidArgumentException('N11 zorunlu özellik doldurulamadı: '.(string) ($row['attributeName'] ?? $attrId));
        }

        if ($attributes === []) {
            $attributes[] = [
                'id' => 1,
                'valueId' => null,
                'customValue' => $brandName,
            ];
        }

        return $attributes;
    }

    /**
     * @param  list<array<string, mixed>>  $values
     * @param  string|list<string>  $candidates
     */
    private function matchAttributeValue(array $values, string|array $candidates): ?int
    {
        $needles = array_map(
            fn ($v) => mb_strtolower(trim((string) $v)),
            is_array($candidates) ? $candidates : [$candidates]
        );

        foreach ($values as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = mb_strtolower(trim((string) ($row['value'] ?? '')));
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || $label === '') {
                continue;
            }
            foreach ($needles as $needle) {
                if ($needle !== '' && ($label === $needle || str_contains($label, $needle) || str_contains($needle, $label))) {
                    return $id;
                }
            }
        }

        return null;
    }

    private function fallbackCustomValue(string $attrName, Product $product): string
    {
        return match (true) {
            str_contains($attrName, 'model') => (string) ($product->sku ?: $product->id),
            str_contains($attrName, 'tip') || str_contains($attrName, 'ürün') => 'Su Pompası',
            default => (string) ($product->brand?->name ?: 'Standart'),
        };
    }

    /**
     * @return list<array{url: string, order: int}>
     */
    private function collectImages(Product $product): array
    {
        $candidates = [];
        if (filled($product->image)) {
            $candidates[] = (string) $product->image;
        }
        foreach ($product->images as $image) {
            if (filled($image->path)) {
                $candidates[] = (string) $image->path;
            }
        }

        $urls = [];
        $order = 0;
        foreach ($candidates as $path) {
            $url = $this->resolvePublicHttpsUrl($path, (string) ($product->sku ?: $product->id), $order);
            if ($url === null) {
                continue;
            }
            $urls[] = ['url' => $url, 'order' => $order];
            $order++;
            if ($order >= 8) {
                break;
            }
        }

        return $urls;
    }

    private function resolvePublicHttpsUrl(string $path, string $sku, int $index): ?string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $url = str_starts_with($path, 'http://') ? 'https://'.substr($path, 7) : $path;
            return $this->isReachableImage($url) ? $url : null;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        foreach ($this->candidateSeedFiles($relative, $sku) as $seedFile) {
            $cdn = $this->cdnUrlForRepoFile($seedFile, $relative.$sku.$index);
            if ($cdn !== null && $this->isReachableImage($cdn)) {
                return $cdn;
            }
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($relative)) {
            return null;
        }

        $info = @getimagesize(storage_path('app/public/'.$relative));
        if ($info === false) {
            return null;
        }
        $w = (int) ($info[0] ?? 0);
        $h = (int) ($info[1] ?? 0);
        if ($w < 400 || $h < 400) {
            return null;
        }

        // Son çare: site public URL (HTTPS).
        $public = 'https://kosarticaret.com/storage/'.$relative;
        if ($this->isReachableImage($public)) {
            return $public;
        }

        return null;
    }

    /**
     * @return list<string> absolute file paths
     */
    private function candidateSeedFiles(string $sourceRelative, string $sku): array
    {
        $candidates = [];
        $basename = basename($sourceRelative);
        $skuKey = strtolower(preg_replace('/[^a-z0-9]+/i', '', $sku) ?: '');

        $patterns = [];
        if ($basename !== '' && $basename !== '.' && $basename !== '..') {
            $patterns[] = database_path('seed-assets/*/'.$basename);
            $patterns[] = database_path('seed-assets/*/*/'.$basename);
        }
        if ($skuKey !== '') {
            $patterns[] = database_path('seed-assets/*/'.$skuKey.'*.jpg');
            $patterns[] = database_path('seed-assets/*/'.$skuKey.'-*.jpg');
            // HD500 → hd500-1.jpg
            $patterns[] = database_path('seed-assets/*/'.strtolower($sku).'*.jpg');
            $patterns[] = database_path('seed-assets/*/'.strtolower($sku).'-*.jpg');
        }

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                if (is_file($file)) {
                    $candidates[] = $file;
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    private function cdnUrlForRepoFile(string $absolutePath, string $bustSeed): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $repoRelative = str_replace('\\', '/', substr($absolutePath, strlen(base_path()) + 1));
        $repo = trim((string) config('marketplace.n11.github_repo', 'kosarteknikservis-pixel/kosarticaret-'), '/');
        $branch = trim((string) config('marketplace.n11.github_branch', 'main'), '/');
        $bust = substr(md5($bustSeed.now()->format('YmdHis')), 0, 8);

        return sprintf(
            'https://cdn.jsdelivr.net/gh/%s@%s/%s?v=%s',
            $repo,
            $branch,
            ltrim($repoRelative, '/'),
            $bust
        );
    }

    private function isReachableImage(string $url): bool
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; KosarN11Preflight/1.0)', 'Range' => 'bytes=0-1023'])
                ->get($url);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful() && $response->status() !== 206) {
            return false;
        }

        $type = strtolower((string) $response->header('Content-Type'));

        return $response->body() !== '' && ($type === '' || str_starts_with($type, 'image/'));
    }

    private function resolveOptionalEan13(Product $product): ?string
    {
        $raw = trim((string) ($product->barcode ?: ''));
        if ($raw !== '' && preg_match('/^\d{13}$/', $raw) && $this->isValidEan13($raw)) {
            return $raw;
        }

        // Ulusal/dahili EAN üret (katalog eşleşmesi yoksa yine kabul edilir).
        $body = '200'.str_pad((string) max(0, ((int) $product->id) % 1_000_000_000), 9, '0', STR_PAD_LEFT);

        return $body.$this->ean13CheckDigit($body);
    }

    private function isValidEan13(string $code): bool
    {
        return $this->ean13CheckDigit(substr($code, 0, 12)) === (int) $code[12];
    }

    private function ean13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $d = (int) $twelveDigits[$i];
            $sum += ($i % 2 === 0) ? $d : $d * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    private function sanitizeDescription(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#</?a\b[^>]*>#i', '', $html) ?? $html;
        $allowed = '<p><br><ul><ol><li><h2><h3><h4><strong><b><em><table><thead><tbody><tr><th><td>';
        $html = trim(strip_tags($html, $allowed));
        $html = preg_replace('/https?:\/\/[^\s<]+/iu', '', $html) ?? $html;
        $html = preg_replace('/www\.[^\s<]+/iu', '', $html) ?? $html;

        return trim(preg_replace("/\n{3,}/", "\n\n", $html) ?? $html);
    }

    private function resolveCategoryMapping(Product $product): ?MarketplaceCategoryMapping
    {
        $categoryIds = $product->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            return null;
        }

        return MarketplaceCategoryMapping::query()
            ->where('channel_key', 'n11')
            ->whereIn('category_id', $categoryIds)
            ->first();
    }

    private function resolveBrandMapping(Product $product): ?MarketplaceBrandMapping
    {
        if (! $product->brand_id) {
            return null;
        }

        return MarketplaceBrandMapping::query()
            ->where('channel_key', 'n11')
            ->where('brand_id', $product->brand_id)
            ->first();
    }
}
