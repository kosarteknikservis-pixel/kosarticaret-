<?php

namespace App\Services\Marketplace\PttAvm;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class PttAvmListingPayloadBuilder
{
    public function __construct(
        private MarketplaceChannelPricing $pricing,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>}
     */
    public function buildUpsertPayload(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $priceOverride = null,
    ): array {
        $product->loadMissing(['brand', 'categories', 'images']);

        $categoryMapping = $this->resolveCategoryMapping($product);
        $brandMapping = $this->resolveBrandMapping($product);
        if ($categoryMapping === null) {
            throw new InvalidArgumentException('PTT AVM kategori eşleştirmesi bulunamadı.');
        }
        if ($brandMapping === null) {
            throw new InvalidArgumentException('PTT AVM marka eşleştirmesi bulunamadı.');
        }

        $sale = round($this->pricing->salePrice($product, $channel, $listing, $priceOverride), 2);
        if ($sale <= 0) {
            throw new InvalidArgumentException('PTT AVM satış fiyatı 0 olamaz.');
        }

        $stock = $this->pricing->stockQuantity($product, $channel, $listing);
        if ($stock < 1) {
            throw new InvalidArgumentException(
                'PTT AVM stok 0 gönderilemez (SKU '.(string) ($product->sku ?: $product->id).').'
            );
        }

        $images = $this->collectImages($product);
        if ($images === []) {
            throw new InvalidArgumentException('PTT AVM için en az bir HTTPS görsel gerekli.');
        }

        $title = mb_substr(trim((string) $product->name), 0, 200);
        if ($title === '') {
            throw new InvalidArgumentException('PTT AVM ürün başlığı boş.');
        }

        $stockCode = mb_substr(strtoupper(trim((string) ($product->sku ?: 'P'.$product->id))), 0, 64);
        $barcode = $this->resolveSellerBarcode($product, $stockCode);

        $vat = (int) round($product->vatRateValue());
        if (! in_array($vat, [0, 1, 10, 20], true)) {
            $vat = 20;
        }
        $priceWithoutVat = $vat > 0 ? round($sale / (1 + ($vat / 100)), 2) : $sale;

        $short = trim(strip_tags((string) ($product->short_description ?: '')));
        if ($short === '') {
            $short = mb_substr($title, 0, 250);
        }
        $long = $this->sanitizeDescription(
            (string) ($product->description ?: $product->short_description ?: $product->name)
        );
        if (mb_strlen(strip_tags($long)) < 40) {
            throw new InvalidArgumentException('PTT AVM açıklama çok kısa.');
        }

        $delivery = max(0, min(30, (int) $channel->setting(
            'estimated_courier_delivery',
            config('marketplace.pttavm.estimated_courier_delivery', 2)
        )));

        $brandName = trim((string) ($brandMapping->external_brand_name ?: $product->brand?->name ?: ''));
        if ($this->isTroyOrigin($product)) {
            $brandName = 'Sumak';
            $title = $this->scrubTroyText($title);
            $long = $this->scrubTroyText($long);
            $short = $this->scrubTroyText($short);
        }

        $item = [
            'categoryId' => (int) $categoryMapping->external_category_id,
            'barcode' => $barcode,
            'productCode' => $stockCode,
            'name' => $title,
            'priceWithVat' => $sale,
            'priceWithoutVat' => $priceWithoutVat,
            'vatRate' => $vat,
            'quantity' => $stock,
            'discount' => 0,
            'desi' => max(1, (int) round((float) ($product->desi ?? 1))),
            'shortDescription' => mb_substr($short, 0, 500),
            'longDescription' => $long,
            'images' => $images,
            'active' => true,
            'singleBox' => true,
            'estimatedCourierDelivery' => $delivery,
            'warrantyDuration' => 24,
            'warrantySupplier' => $brandName !== '' ? $brandName : 'Üretici',
            'brand' => $brandName !== '' ? $brandName : null,
            'isCargoFromSupplier' => false,
            'ean' => $this->resolveEan13($product),
            'gtin' => $this->resolveEan13($product),
            'attributes' => [
                ['definition' => 'Marka', 'value' => $brandName !== '' ? $brandName : 'Diğer'],
                ['definition' => 'Garanti Süresi', 'value' => '24 Ay'],
            ],
        ];

        return ['items' => [$item]];
    }

    private function resolveCategoryMapping(Product $product): ?MarketplaceCategoryMapping
    {
        $categoryIds = $product->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            return null;
        }

        return MarketplaceCategoryMapping::query()
            ->where('channel_key', 'pttavm')
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('id')
            ->first();
    }

    private function resolveBrandMapping(Product $product): ?MarketplaceBrandMapping
    {
        if ($this->isTroyOrigin($product)) {
            $sumakBrandId = \App\Models\Brand::query()->where('slug', 'sumak')->value('id');
            if ($sumakBrandId) {
                $mapped = MarketplaceBrandMapping::query()
                    ->where('channel_key', 'pttavm')
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
            ->where('channel_key', 'pttavm')
            ->where('brand_id', $product->brand_id)
            ->first();
    }

    private function resolveSellerBarcode(Product $product, string $stockCode): string
    {
        $raw = trim((string) ($product->barcode ?: ''));
        if ($raw !== '' && preg_match('/^\d{13}$/', $raw) && $this->isValidEan13($raw)) {
            $prefix = (int) substr($raw, 0, 3);
            if ($prefix < 200 || $prefix > 299) {
                return $raw;
            }
        }

        $barcode = preg_replace('/\s+/', '', $stockCode) ?: ('P'.$product->id);
        if (mb_strlen($barcode) < 6) {
            $barcode = str_pad($barcode, 6, '0', STR_PAD_RIGHT);
        }

        return mb_substr($barcode, 0, 40);
    }

    private function resolveEan13(Product $product): ?string
    {
        $raw = trim((string) ($product->barcode ?: ''));
        if ($raw !== '' && preg_match('/^\d{13}$/', $raw) && $this->isValidEan13($raw)) {
            $prefix = (int) substr($raw, 0, 3);
            if ($prefix < 200 || $prefix > 299) {
                return $raw;
            }
        }

        return null;
    }

    private function isValidEan13(string $code): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $d = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $d : $d * 3;
        }

        return ((10 - ($sum % 10)) % 10) === (int) $code[12];
    }

    private function sanitizeDescription(string $html): string
    {
        $html = preg_replace('/\s(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;
        $html = strip_tags($html, '<p><br><ul><ol><li><strong><b><em><i><h2><h3><h4><table><thead><tbody><tr><th><td>');

        return trim(preg_replace("/\n{3,}/", "\n\n", $html) ?? $html);
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

        $repo = trim((string) config('marketplace.pttavm.github_repo', 'kosarteknikservis-pixel/kosarticaret-'), '/');
        $branch = trim((string) config('marketplace.pttavm.github_branch', 'main'), '/');
        $basename = basename($relative);
        $brandGuess = '';
        if (preg_match('#^products/([^/]+)/#', $relative, $m)) {
            $brandGuess = $m[1];
        }
        $seedCandidates = array_values(array_filter([
            $brandGuess !== '' ? 'database/seed-assets/'.$brandGuess.'/'.$basename : null,
            'database/seed-assets/'.mb_strtolower($sku).'.jpg',
        ]));

        foreach ($seedCandidates as $seedFile) {
            $cdn = 'https://cdn.jsdelivr.net/gh/'.$repo.'@'.$branch.'/'.$seedFile
                .'?v='.substr(sha1($seedFile.$sku.$index), 0, 8);
            if ($this->isReachableImage($cdn)) {
                return $cdn;
            }
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($relative)) {
            return null;
        }

        $public = 'https://kosarticaret.com/storage/'.$relative;

        return $this->isReachableImage($public) ? $public : null;
    }

    private function isReachableImage(string $url): bool
    {
        try {
            $response = Http::timeout(8)->withHeaders(['User-Agent' => 'KosarPttAvm/1.0'])->head($url);
            if ($response->successful()) {
                return true;
            }
            $get = Http::timeout(8)->withHeaders(['User-Agent' => 'KosarPttAvm/1.0', 'Range' => 'bytes=0-0'])->get($url);

            return $get->successful() || $get->status() === 206;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isTroyOrigin(Product $product): bool
    {
        $brandSlug = mb_strtolower(trim((string) ($product->brand?->slug ?? '')), 'UTF-8');
        $productSlug = mb_strtolower(trim((string) ($product->slug ?? '')), 'UTF-8');

        return $brandSlug === 'troy' || str_starts_with($productSlug, 'troy-');
    }

    private function scrubTroyText(string $text): string
    {
        return trim(preg_replace('/\bTroy\b/iu', 'Sumak', $text) ?? $text);
    }
}
