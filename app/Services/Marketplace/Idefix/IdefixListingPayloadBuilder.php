<?php

namespace App\Services\Marketplace\Idefix;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class IdefixListingPayloadBuilder
{
    public function __construct(
        private MarketplaceChannelPricing $pricing,
    ) {}

    /**
     * @return array{products: list<array<string, mixed>>}
     */
    public function buildCreatePayload(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $priceOverride = null,
        ?IdefixApiClient $client = null,
    ): array {
        $product->loadMissing(['brand', 'categories', 'images']);

        $categoryMapping = $this->resolveCategoryMapping($product);
        $brandMapping = $this->resolveBrandMapping($product);
        if ($categoryMapping === null) {
            throw new InvalidArgumentException('Idefix kategori eşleştirmesi bulunamadı.');
        }
        if ($brandMapping === null) {
            throw new InvalidArgumentException('Idefix marka eşleştirmesi bulunamadı.');
        }

        $sale = round($this->pricing->salePrice($product, $channel, $listing, $priceOverride), 2);
        if ($sale <= 0) {
            throw new InvalidArgumentException('Idefix satış fiyatı 0 olamaz.');
        }

        $list = round($this->pricing->listPrice($product, $channel, $listing, $priceOverride), 2);
        if ($list < $sale) {
            $list = $sale;
        }

        $stock = $this->pricing->stockQuantity($product, $channel, $listing);
        if ($stock < 1) {
            throw new InvalidArgumentException(
                'Idefix stok 0 gönderilemez (SKU '.(string) ($product->sku ?: $product->id).').'
            );
        }

        // Orijinal görseller (jsDelivr seed / site). Troy etiketli HD görsellerde sansür/cover yok.
        $images = $this->collectImages($product);
        if ($images === []) {
            throw new InvalidArgumentException('Idefix için en az bir HTTPS görsel gerekli.');
        }

        $asSumak = $this->isTroyOrigin($product);
        $description = $this->sanitizeDescription(
            (string) ($product->description ?: $product->short_description ?: $product->name)
        );
        $title = trim((string) $product->name);
        if ($asSumak) {
            $title = $this->scrubTroyText($title);
            $description = $this->scrubTroyText($description);
        }
        if (mb_strlen(strip_tags($description)) < 40) {
            throw new InvalidArgumentException('Idefix açıklama çok kısa.');
        }

        $stockCode = mb_substr(strtoupper(trim((string) ($product->sku ?: 'P'.$product->id))), 0, 64);
        $title = mb_substr($title, 0, 200);
        if ($title === '') {
            throw new InvalidArgumentException('Idefix ürün başlığı boş.');
        }

        $brandId = (int) $brandMapping->external_brand_id;
        $brandId = $this->skuBrandOverride($product, $brandId);
        if ($brandId <= 0) {
            throw new InvalidArgumentException('Idefix marka ID geçersiz. --map-brand ile API’den eşleştirin.');
        }

        $categoryId = (int) $categoryMapping->external_category_id;
        $categoryId = $this->skuCategoryOverride($product, $categoryId);
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Idefix kategori ID geçersiz.');
        }

        $deliveryDuration = max(1, (int) $channel->setting(
            'delivery_duration',
            config('marketplace.idefix.delivery_duration', 2)
        ));

        // Idefix doküman örnekleri 0/1/8/18; 20 çoğu kategori için de geçerli, aksi halde 18'e düş.
        $vat = (int) round($product->vatRateValue());
        if (! in_array($vat, [0, 1, 8, 10, 18, 20], true)) {
            $vat = 20;
        }

        $attributes = $this->buildAttributes($product, $brandMapping, $categoryId, $client);

        $productRow = [
            'barcode' => $this->resolveBarcode($product, $stockCode),
            'title' => $title,
            'productMainId' => $stockCode,
            'brandId' => $brandId,
            'categoryId' => $categoryId,
            'inventoryQuantity' => $stock,
            'vendorStockCode' => $stockCode,
            'desi' => 0,
            'description' => $description,
            'price' => $sale,
            'comparePrice' => $list,
            'vatRate' => $vat,
            'deliveryDuration' => $deliveryDuration,
            'deliveryType' => 'regular',
            'images' => $images,
            'attributes' => $attributes,
        ];

        return ['products' => [$productRow]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildAttributes(
        Product $product,
        MarketplaceBrandMapping $brandMapping,
        int $categoryId,
        ?IdefixApiClient $client,
    ): array {
        if ($client === null || $categoryId <= 0) {
            throw new InvalidArgumentException('Idefix zorunlu özellikler için API istemcisi gerekli.');
        }

        $catalog = $client->getCategoryAttributes($categoryId);
        $rows = $catalog['categoryAttributes']
            ?? data_get($catalog, 'items.0.categoryAttributes')
            ?? data_get($catalog, 'data.categoryAttributes')
            ?? [];
        if (! is_array($rows)) {
            $rows = [];
        }
        if ($rows === [] && ! isset($catalog['id']) && ! isset($catalog['name'])) {
            throw new InvalidArgumentException('Idefix kategori özellikleri alınamadı (leaf kategori mi?).');
        }

        $brandName = (string) ($brandMapping->external_brand_name ?: $product->brand?->name ?: 'Diğer');
        $attributes = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $isRequired = (bool) ($row['required'] ?? $row['isMandatory'] ?? false);
            if (! $isRequired) {
                continue;
            }

            $attrId = (int) ($row['attributeId'] ?? 0);
            $title = mb_strtolower((string) ($row['attributeTitle'] ?? ''), 'UTF-8');
            $allowCustom = (bool) ($row['allowCustom'] ?? false);
            $values = is_array($row['attributeValues'] ?? null) ? $row['attributeValues'] : [];

            if ($attrId <= 0) {
                continue;
            }

            if (str_contains($title, 'marka')) {
                $matched = $this->matchAttributeValue($values, $brandName);
                if ($matched !== null) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => $matched];
                } elseif ($allowCustom) {
                    $attributes[] = ['attributeId' => $attrId, 'customAttributeValue' => $brandName];
                } else {
                    throw new InvalidArgumentException('Idefix zorunlu marka özelliği eşleştirilemedi.');
                }
                continue;
            }

            if (str_contains($title, 'garanti')) {
                $matched = $this->matchAttributeValue($values, ['24 Ay', '24 ay', '2 Yıl', '2 yıl', '24', '2']);
                if ($matched !== null) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => $matched];
                } elseif ($allowCustom) {
                    $attributes[] = ['attributeId' => $attrId, 'customAttributeValue' => '24 Ay'];
                } elseif ($values !== []) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => (int) ($values[0]['id'] ?? 0)];
                }
                continue;
            }

            if (str_contains($title, 'mense') || str_contains($title, 'menşei') || str_contains($title, 'üretil') || str_contains($title, 'origin')) {
                $matched = $this->matchAttributeValue($values, ['Türkiye', 'Turkiye', 'TR']);
                if ($matched !== null) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => $matched];
                } elseif ($allowCustom) {
                    $attributes[] = ['attributeId' => $attrId, 'customAttributeValue' => 'Türkiye'];
                } elseif ($values !== []) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => (int) ($values[0]['id'] ?? 0)];
                }
                continue;
            }

            if (str_contains($title, 'renk')) {
                $matched = $this->matchAttributeValue($values, ['Standart', 'Çok Renkli', 'Diğer', 'Siyah']);
                if ($matched !== null) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => $matched];
                } elseif ($allowCustom) {
                    $attributes[] = ['attributeId' => $attrId, 'customAttributeValue' => 'Standart'];
                } elseif ($values !== []) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => (int) ($values[0]['id'] ?? 0)];
                }
                continue;
            }

            if (str_contains($title, 'tip') || str_contains($title, 'ürün tipi') || str_contains($title, 'urun tipi')) {
                $tipCandidates = $this->tipCandidatesForIdefix($product);
                $matched = $this->matchAttributeValue($values, $tipCandidates);
                if ($matched !== null) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => $matched];
                } elseif ($allowCustom) {
                    $attributes[] = ['attributeId' => $attrId, 'customAttributeValue' => $tipCandidates[0] ?? 'Su Pompası'];
                } elseif ($values !== []) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => (int) ($values[0]['id'] ?? 0)];
                }
                continue;
            }

            if (! $allowCustom && $values !== []) {
                $firstId = (int) ($values[0]['id'] ?? 0);
                if ($firstId > 0) {
                    $attributes[] = ['attributeId' => $attrId, 'attributeValueId' => $firstId];
                    continue;
                }
            }

            if ($allowCustom) {
                $attributes[] = [
                    'attributeId' => $attrId,
                    'customAttributeValue' => $this->fallbackCustomValue($title, $product),
                ];
                continue;
            }

            throw new InvalidArgumentException(
                'Idefix zorunlu özellik doldurulamadı: '.(string) ($row['attributeTitle'] ?? $attrId)
            );
        }

        // Bazı leaf kategorilerde required=true yok; boş attributes ile create kabul edilebiliyor.
        return $attributes;
    }

    /**
     * @param  list<array<string, mixed>>  $values
     * @param  string|list<string>  $candidates
     */
    private function matchAttributeValue(array $values, string|array $candidates): ?int
    {
        $needles = array_map(
            fn ($v) => mb_strtolower(trim((string) $v), 'UTF-8'),
            is_array($candidates) ? $candidates : [$candidates]
        );

        foreach ($values as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = mb_strtolower(trim((string) ($row['name'] ?? $row['value'] ?? '')), 'UTF-8');
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

    private function fallbackCustomValue(string $attrTitle, Product $product): string
    {
        return match (true) {
            str_contains($attrTitle, 'model') => (string) ($product->sku ?: $product->id),
            str_contains($attrTitle, 'tip') || str_contains($attrTitle, 'ürün') => 'Su Pompası',
            default => (string) ($product->brand?->name ?: 'Standart'),
        };
    }

    private function resolveBarcode(Product $product, string $stockCode): string
    {
        $raw = trim((string) ($product->barcode ?: ''));
        if ($raw !== '' && preg_match('/^\d{13}$/', $raw) && $this->isValidEan13($raw)) {
            $prefix = (int) substr($raw, 0, 3);
            // Dahili 200–299 EAN katalog yanlış eşleşmeye yol açabilir; SKU kullan.
            if ($prefix < 200 || $prefix > 299) {
                return $raw;
            }
        }

        // Idefix barkod zorunlu ve min 6 karakter; üretici EAN yoksa SKU (gerekirse pad).
        $barcode = preg_replace('/\s+/', '', $stockCode) ?: ('P'.$product->id);
        if (mb_strlen($barcode) < 6) {
            $barcode = str_pad($barcode, 6, '0', STR_PAD_RIGHT);
        }

        return mb_substr($barcode, 0, 40);
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

    /**
     * @return list<array{url: string}>
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
        $index = 0;
        foreach ($candidates as $path) {
            $url = $this->resolvePublicHttpsUrl($path, (string) ($product->sku ?: $product->id), $index);
            if ($url === null) {
                continue;
            }
            $urls[] = ['url' => $url];
            $index++;
            if ($index >= 8) {
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

        $public = 'https://kosarticaret.com/storage/'.$relative;
        if ($this->isReachableImage($public)) {
            return $public;
        }

        return null;
    }

    /**
     * @return list<string>
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
        $repo = trim((string) config('marketplace.idefix.github_repo', 'kosarteknikservis-pixel/kosarticaret-'), '/');
        $branch = trim((string) config('marketplace.idefix.github_branch', 'main'), '/');
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
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; KosarIdefixPreflight/1.0)',
                    'Range' => 'bytes=0-1023',
                ])
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

        $mappings = MarketplaceCategoryMapping::query()
            ->where('channel_key', 'idefix')
            ->whereIn('category_id', $categoryIds)
            ->get();

        if ($mappings->isEmpty()) {
            return null;
        }

        // Ürün birden fazla kategorideyse en spesifik leaf kazanır.
        // su-pompalari → Su Motorları (#530110498) en genel; Bahçe Hidrofonları kullanılmaz.
        $priority = [
            '771397498' => 1, // Tekne Hidrofor (karavan)
            '231078808' => 2, // Hidroforlar
            '530141372' => 3, // Santrifüj Pompalar
            '530138605' => 4, // Bahçe Pompası (tahliye)
            '530114652' => 5, // Dalgıç Pompa
            '530110498' => 6, // Su Motorları / jet
        ];

        return $mappings->sortBy(function (MarketplaceCategoryMapping $m) use ($priority) {
            $id = (string) $m->external_category_id;

            return $priority[$id] ?? 99;
        })->first();
    }

    private function resolveBrandMapping(Product $product): ?MarketplaceBrandMapping
    {
        // Troy kökenli ürünler Idefix’te Sumak marka ID ile gider.
        if ($this->isTroyOrigin($product)) {
            $sumak = MarketplaceBrandMapping::query()
                ->where('channel_key', 'idefix')
                ->where('external_brand_name', 'like', 'Sumak')
                ->whereRaw('CAST(external_brand_id AS UNSIGNED) > 0')
                ->first();

            if ($sumak !== null) {
                return $sumak;
            }

            // Sumak yerel marka_id eşlemesi
            $sumakBrandId = \App\Models\Brand::query()->where('slug', 'sumak')->value('id');
            if ($sumakBrandId) {
                $mapped = MarketplaceBrandMapping::query()
                    ->where('channel_key', 'idefix')
                    ->where('brand_id', $sumakBrandId)
                    ->first();
                if ($mapped !== null) {
                    return $mapped;
                }
            }
        }

        if (! $product->brand_id) {
            return null;
        }

        return MarketplaceBrandMapping::query()
            ->where('channel_key', 'idefix')
            ->where('brand_id', $product->brand_id)
            ->first();
    }

    private function isTroyOrigin(Product $product): bool
    {
        $brandSlug = mb_strtolower(trim((string) ($product->brand?->slug ?? '')), 'UTF-8');
        $productSlug = mb_strtolower(trim((string) ($product->slug ?? '')), 'UTF-8');
        $brandName = mb_strtolower(trim((string) ($product->brand?->name ?? '')), 'UTF-8');

        return $brandSlug === 'troy'
            || str_starts_with($productSlug, 'troy-')
            || $brandName === 'troy';
    }

    private function skuBrandOverride(Product $product, int $brandId): int
    {
        $sku = mb_strtoupper(trim((string) ($product->sku ?: '')), 'UTF-8');
        // Bu SKU’larda katalog #20038761 istiyor (#20038760 BRAND_FAILED).
        if (in_array($sku, ['SMJK100', 'SDF152', 'SDF102'], true)) {
            return 20038761;
        }

        return $brandId;
    }

    private function skuCategoryOverride(Product $product, int $categoryId): int
    {
        $sku = mb_strtoupper(trim((string) ($product->sku ?: '')), 'UTF-8');
        // SBRM bıçaklı foseptik: create CATEGORY_FAILED → Su Motorları
        if (in_array($sku, ['SBRM18-2-P', 'SBRM182P', 'SBRM18-2P'], true)) {
            return 530110498;
        }
        // Jet SMJK + SDF152: Idefix create CATEGORY_FAILED → Santrifüj leaf
        if (in_array($sku, ['SMJK100', 'SDF152'], true)) {
            return 530141372;
        }

        return $categoryId;
    }

    /** @return list<string> */
    private function tipCandidatesForIdefix(Product $product): array
    {
        $hay = mb_strtolower(
            $product->name.' '.$product->categories->pluck('slug')->implode(' '),
            'UTF-8'
        );

        if (str_contains($hay, 'hidrofor') || str_contains($hay, 'karavan')) {
            return ['Hidrofor', 'Paket Hidrofor', 'Su Pompası', 'Dalgıç Pompa'];
        }
        if (str_contains($hay, 'jet')) {
            return ['Jet Pompa', 'Su Pompası', 'Su Motoru', 'Dalgıç Pompa'];
        }
        if (str_contains($hay, 'preferikal') || str_contains($hay, 'santrif')) {
            return ['Santrifüj Pompa', 'Su Pompası', 'Preferikal', 'Su Motoru'];
        }
        if (str_contains($hay, 'dalgic') || str_contains($hay, 'dalgıç') || str_contains($hay, 'drenaj') || str_contains($hay, 'foseptik')) {
            return ['Dalgıç Pompa', 'Temiz Su Dalgıç', 'Dalgıç', 'Su Pompası', 'Drenaj Pompası'];
        }

        return ['Su Pompası', 'Dalgıç Pompa', 'Hidrofor', 'Su Motoru'];
    }

    private function scrubTroyText(string $text): string
    {
        $text = preg_replace('/\b[Tt]roy\b/u', 'Sumak', $text) ?? $text;
        $text = preg_replace('/\bTROY\b/u', 'Sumak', $text) ?? $text;
        $text = preg_replace('/\b[Yy]akar\b/u', '', $text) ?? $text;
        $text = str_ireplace('/marka/troy', '/marka/sumak', $text);
        $text = preg_replace('/\s{2,}/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
