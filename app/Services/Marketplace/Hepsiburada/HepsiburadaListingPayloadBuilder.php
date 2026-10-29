<?php

namespace App\Services\Marketplace\Hepsiburada;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceChannelPricing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class HepsiburadaListingPayloadBuilder
{
    public function __construct(
        private MarketplaceChannelPricing $pricing,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildImportPayload(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $priceOverride = null,
        ?HepsiburadaApiClient $client = null,
    ): array {
        $product->loadMissing(['brand', 'categories', 'images']);

        $categoryMapping = $this->resolveCategoryMapping($product, $channel->key);
        $brandMapping = $this->resolveBrandMapping($product, $channel->key);

        if ($categoryMapping === null) {
            throw new InvalidArgumentException('Hepsiburada kategori eşleştirmesi bulunamadı.');
        }

        if ($brandMapping === null) {
            throw new InvalidArgumentException('Hepsiburada marka eşleştirmesi bulunamadı.');
        }

        $images = $this->collectImageUrls($product);
        if ($images === []) {
            throw new InvalidArgumentException('Hepsiburada için en az bir görsel URL gerekli.');
        }

        $description = $this->sanitizeDescriptionHtml(
            (string) ($product->description ?: $product->short_description ?: $product->name)
        );
        if ($description === '') {
            throw new InvalidArgumentException('Ürün açıklaması boş.');
        }

        $salePrice = $this->pricing->salePrice($product, $channel, $listing, $priceOverride);
        $stock = $this->pricing->stockQuantity($product, $channel, $listing);
        $merchantSku = strtoupper((string) ($product->sku ?: 'P'.$product->id));
        $barcode = filled($product->barcode) ? (string) $product->barcode : $merchantSku;
        $brandName = (string) ($brandMapping->external_brand_name ?: $product->brand?->name ?: 'Diğer');
        $weight = $product->weight_kg !== null && (float) $product->weight_kg > 0
            ? (string) round((float) $product->weight_kg, 2)
            : (string) max(1, (int) ($product->desi() ?? 1));

        $attributes = [
            'merchantSku' => $merchantSku,
            'VaryantGroupID' => $merchantSku,
            'Barcode' => $barcode,
            'UrunAdi' => mb_substr((string) $product->name, 0, 200),
            'UrunAciklamasi' => $description,
            'Marka' => $brandName,
            'GarantiSuresi' => '24',
            'kg' => $weight,
            'tax_vat_rate' => (string) (int) round($product->vatRateValue()),
            'price' => $this->formatCatalogPrice($salePrice),
            'stock' => (string) max(0, $stock),
        ];

        foreach (array_values($images) as $index => $url) {
            if ($index >= 10) {
                break;
            }
            $attributes['Image'.($index + 1)] = $url;
        }

        $this->assertImagesPubliclyReachable($attributes);

        if ($client !== null) {
            $attributes = $this->enrichRequiredAttributes(
                $attributes,
                $client,
                (int) $categoryMapping->external_category_id,
                $product,
            );
        }

        return [[
            'categoryId' => (int) $categoryMapping->external_category_id,
            'merchant' => trim((string) data_get($channel->credentials, 'merchant_id', '')),
            'attributes' => $attributes,
        ]];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function enrichRequiredAttributes(
        array $attributes,
        HepsiburadaApiClient $client,
        int $categoryId,
        Product $product,
    ): array {
        if ($categoryId <= 0) {
            return $attributes;
        }

        $catalog = $client->getCategoryAttributes($categoryId);
        $rows = $catalog['data'] ?? $catalog['attributes'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return $attributes;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = (string) ($row['name'] ?? $row['id'] ?? '');
            $mandatory = (bool) ($row['mandatory'] ?? $row['required'] ?? false);
            if ($name === '' || ! $mandatory) {
                continue;
            }

            if (array_key_exists($name, $attributes) && filled($attributes[$name])) {
                continue;
            }

            $value = $this->defaultValueForAttribute($name, $product, $attributes);
            if ($value === null || $value === '') {
                throw new InvalidArgumentException(
                    'Hepsiburada zorunlu özellik doldurulamadı: '.$name
                );
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function defaultValueForAttribute(string $name, Product $product, array $attributes): ?string
    {
        $normalized = mb_strtolower(trim($name));

        return match (true) {
            $normalized === 'marka' => (string) ($attributes['Marka'] ?? $product->brand?->name ?? ''),
            $normalized === 'urunadi' || $normalized === 'ürünadı' || str_contains($normalized, 'urunadi') => (string) $product->name,
            str_contains($normalized, 'aciklama') || str_contains($normalized, 'açıklama') => (string) ($attributes['UrunAciklamasi'] ?? $product->short_description ?? $product->name),
            str_contains($normalized, 'garanti') => '24',
            $normalized === 'kg' || str_contains($normalized, 'desi') => (string) ($attributes['kg'] ?? '1'),
            str_contains($normalized, 'mense') || str_contains($normalized, 'menşei') || str_contains($normalized, 'origin') => 'Türkiye',
            str_contains($normalized, 'renk') => 'Standart',
            default => null,
        };
    }

    private function formatCatalogPrice(float $price): string
    {
        return number_format($price, 2, ',', '');
    }

    private function sanitizeDescriptionHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#</?a\b[^>]*>#i', '', $html) ?? $html;
        $allowed = '<p><br><ul><ol><li><h2><h3><h4><strong><b><em><table><thead><tbody><tr><th><td>';
        $html = trim(strip_tags($html, $allowed));

        $blocked = [
            '/garanti\s*süresi[^.<\n]*/iu',
            '/üretici\s+garanti\s+şartlar[^.<\n]*/iu',
            '/iade\s+(koşul|hak|politika)[^.<\n]*/iu',
            '/www\.[^\s<]+/iu',
            '/https?:\/\/[^\s<]+/iu',
            '/\b0\d{3}\s?\d{3}\s?\d{2}\s?\d{2}\b/u',
            '/\b05\d{9}\b/u',
        ];
        foreach ($blocked as $pattern) {
            $html = preg_replace($pattern, '', $html) ?? $html;
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $html) ?? $html);
    }

    private function resolveCategoryMapping(Product $product, string $channelKey): ?MarketplaceCategoryMapping
    {
        $categoryIds = $product->categories()->pluck('categories.id');
        if ($categoryIds->isEmpty()) {
            return null;
        }

        return MarketplaceCategoryMapping::query()
            ->where('channel_key', $channelKey)
            ->whereIn('category_id', $categoryIds)
            ->first();
    }

    private function resolveBrandMapping(Product $product, string $channelKey): ?MarketplaceBrandMapping
    {
        if (! $product->brand_id) {
            return null;
        }

        return MarketplaceBrandMapping::query()
            ->where('channel_key', $channelKey)
            ->where('brand_id', $product->brand_id)
            ->first();
    }

    /**
     * HB katalog kuralları: orijinal JPG/PNG (webp değil), grafik/çizim değil,
     * ürün fotoğrafı yaklaşık kare ve yeterli çözünürlükte olmalı.
     * Güncellemede aynı URL kabul edilmez → her gönderimde benzersiz public kopya.
     *
     * @return list<string>
     */
    private function collectImageUrls(Product $product): array
    {
        $urls = [];
        $candidates = [];

        if (filled($product->image)) {
            $candidates[] = (string) $product->image;
        }

        foreach ($product->images as $image) {
            if (filled($image->path)) {
                $candidates[] = (string) $image->path;
            }
        }

        $stamp = now()->format('YmdHis');
        $index = 0;

        foreach ($candidates as $path) {
            if (! $this->isMarketplaceSafeImagePath($path)) {
                continue;
            }

            $publicPath = $this->publishUniqueMarketplaceCopy($path, (string) ($product->sku ?: $product->id), $stamp, $index);
            $url = $this->absoluteImageUrl($this->publicStorageUrl($publicPath));
            if ($url && ! in_array($url, $urls, true)) {
                $urls[] = $url;
                $index++;
            }

            if (count($urls) >= 10) {
                break;
            }
        }

        if ($urls === []) {
            throw new InvalidArgumentException(
                'Hepsiburada için uygun ürün fotoğrafı yok (grafik/çizim veya düşük çözünürlük elendi).'
            );
        }

        return $urls;
    }

    /**
     * HB aynı URL ile görsel güncellemeyi reddeder; her publish için yeni path.
     */
    private function publishUniqueMarketplaceCopy(string $sourcePath, string $sku, string $stamp, int $index): string
    {
        $relative = ltrim(str_replace('\\', '/', $sourcePath), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return $relative;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($relative)) {
            throw new RuntimeException('Hepsiburada görsel dosyası yok: '.$relative);
        }

        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION) ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $ext = 'jpg';
        }

        $safeSku = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtoupper($sku)) ?: 'SKU';
        $target = sprintf('marketplace/hepsiburada/%s/%s-%d.%s', $safeSku, $stamp, $index + 1, $ext);
        $disk->put($target, $disk->get($relative));

        return $target;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertImagesPubliclyReachable(array $attributes): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $key = 'Image'.$i;
            if (! isset($attributes[$key]) || ! is_string($attributes[$key]) || $attributes[$key] === '') {
                continue;
            }

            $url = $attributes[$key];
            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; KosarHbPreflight/1.0)'])
                    ->head($url);
            } catch (\Throwable $e) {
                throw new InvalidArgumentException(
                    "Hepsiburada {$key} erişilemedi ({$url}): ".$e->getMessage()
                );
            }

            if (! $response->successful()) {
                throw new InvalidArgumentException(
                    "Hepsiburada {$key} HTTP ".$response->status().' döndü: '.$url
                );
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if ($contentType !== '' && ! str_starts_with($contentType, 'image/')) {
                throw new InvalidArgumentException(
                    "Hepsiburada {$key} image/* değil ({$contentType}): ".$url
                );
            }
        }
    }

    private function isMarketplaceSafeImagePath(string $path): bool
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return true;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        $full = storage_path('app/public/'.$relative);
        if (! is_file($full)) {
            return true;
        }

        $info = @getimagesize($full);
        if ($info === false) {
            return false;
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width < 400 || $height < 400) {
            return false;
        }

        // Performans eğrisi / tablo gibi yatay grafik çizimleri HB'de "Görsel" red alır.
        $ratio = max($width, $height) / max(1, min($width, $height));
        if ($ratio > 1.35) {
            return false;
        }

        $mime = (string) ($info['mime'] ?? '');
        if ($mime !== '' && ! in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true)) {
            return false;
        }

        return true;
    }

    private function publicStorageUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            return '/'.$relative;
        }

        return '/storage/'.$relative;
    }

    private function absoluteImageUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            // HB için webp optimize URL yerine mümkünse orijinal jpg tercih et.
            if (str_contains($url, '/_optimized/') && str_ends_with(strtolower($url), '.webp')) {
                $original = preg_replace(
                    '#^(.*?)/_optimized/([^/]+)-product-[a-z0-9]+\.webp$#i',
                    '$1/$2.jpg',
                    $url
                );
                if (is_string($original) && $original !== $url) {
                    return $original;
                }
            }

            return $url;
        }

        $base = rtrim((string) config(
            'marketplace.hepsiburada.public_asset_base_url',
            config('app.url')
        ), '/');

        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/');
        }

        // HB crawler HTTP'yi/redirect'i sorun çıkarabiliyor.
        if (str_starts_with($base, 'http://')) {
            $base = 'https://'.substr($base, strlen('http://'));
        }

        return $base.'/'.ltrim($url, '/');
    }
}
