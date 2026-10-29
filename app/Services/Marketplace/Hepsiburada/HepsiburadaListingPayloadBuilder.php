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
        if ($stock < 1) {
            throw new InvalidArgumentException(
                'Hepsiburada stok 0 gönderilemez (SKU '.(string) ($product->sku ?: $product->id).'). Sitede stok yok veya kanal stok limiti 0.'
            );
        }
        $merchantSku = strtoupper((string) ($product->sku ?: 'P'.$product->id));
        $brandName = (string) ($brandMapping->external_brand_name ?: $product->brand?->name ?: 'Diğer');
        $weight = $product->weight_kg !== null && (float) $product->weight_kg > 0
            ? (string) round((float) $product->weight_kg, 2)
            : (string) max(1, (int) ($product->desi() ?? 1));

        $attributes = [
            'merchantSku' => $merchantSku,
            'VaryantGroupID' => $merchantSku,
            'UrunAdi' => mb_substr((string) $product->name, 0, 200),
            'UrunAciklamasi' => $description,
            'Marka' => $brandName,
            'GarantiSuresi' => '24',
            'kg' => $weight,
            'tax_vat_rate' => (string) (int) round($product->vatRateValue()),
            'price' => $this->formatCatalogPrice($salePrice),
            'stock' => (string) $stock,
        ];

        // HB Barkod zorunlu + EAN-13; SKU metni (4SDF4) kabul edilmez → geçerli EAN üret.
        $attributes['Barcode'] = $this->resolveEan13Barcode($product);

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

    private function resolveEan13Barcode(Product $product): string
    {
        $raw = trim((string) ($product->barcode ?: ''));
        if ($raw !== '' && $this->isValidEan13($raw)) {
            return $raw;
        }

        // Dahili EAN (200…): site barkodunu değiştirmez; HB zorunlu alanını doldurur.
        return $this->generateInternalEan13((int) $product->id);
    }

    private function generateInternalEan13(int $productId): string
    {
        $body = '200'.str_pad((string) max(0, $productId % 1_000_000_000), 9, '0', STR_PAD_LEFT);

        return $body.$this->ean13CheckDigit($body);
    }

    private function isValidEan13(string $code): bool
    {
        if (! preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        return $this->ean13CheckDigit(substr($code, 0, 12)) === (int) $code[12];
    }

    private function ean13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $twelveDigits[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (10 - ($sum % 10)) % 10;
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
     * HB katalog kuralları: orijinal JPG/PNG, grafik değil.
     * Site WAF HB crawler IP'lerini kestigi icin varsayilan mirror: jsDelivr/catbox.
     *
     * @return list<string>
     */
    private function collectImageUrls(Product $product): array
    {
        $urls = [];
        $seenHashes = [];
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

            $relative = $this->normalizePublicRelativePath($path);
            $hash = $this->localImageHash($relative);
            if ($hash !== null && isset($seenHashes[$hash])) {
                continue;
            }

            $publicPath = $this->publishUniqueMarketplaceCopy($relative, (string) ($product->sku ?: $product->id), $stamp, $index);
            $url = $this->resolveMirroredImageUrl($relative, $publicPath);
            if ($url && ! in_array($url, $urls, true)) {
                $urls[] = $url;
                if ($hash !== null) {
                    $seenHashes[$hash] = true;
                }
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

    private function normalizePublicRelativePath(string $path): string
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        return $relative;
    }

    private function localImageHash(string $relative): ?string
    {
        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return null;
        }

        $full = storage_path('app/public/'.$relative);
        if (! is_file($full)) {
            return null;
        }

        return md5_file($full) ?: null;
    }

    /**
     * Site URL yerine HB'nin indirebildigi CDN/ayna.
     */
    private function resolveMirroredImageUrl(string $sourceRelative, string $publishedRelative): string
    {
        $mode = strtolower((string) config('marketplace.hepsiburada.image_mirror', 'auto'));

        if ($mode === 'site') {
            return (string) $this->absoluteImageUrl($this->publicStorageUrl($publishedRelative));
        }

        if (in_array($mode, ['auto', 'jsdelivr', 'github'], true)) {
            $jsdelivr = $this->jsdelivrUrlForSource($sourceRelative);
            if ($jsdelivr !== null && $this->isPublicImageReachable($jsdelivr)) {
                return $jsdelivr;
            }
            if ($mode === 'jsdelivr' || $mode === 'github') {
                throw new InvalidArgumentException(
                    'Hepsiburada jsDelivr aynası için erişilebilir seed görseli yok: '.$sourceRelative
                );
            }
        }

        if (in_array($mode, ['auto', 'catbox'], true)) {
            return $this->uploadToCatbox($publishedRelative);
        }

        return (string) $this->absoluteImageUrl($this->publicStorageUrl($publishedRelative));
    }

    private function jsdelivrUrlForSource(string $sourceRelative): ?string
    {
        $basename = basename($sourceRelative);
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return null;
        }

        $seedRoot = database_path('seed-assets');
        if (! is_dir($seedRoot)) {
            return null;
        }

        $matches = glob($seedRoot.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.$basename) ?: [];
        if ($matches === []) {
            $matches = glob($seedRoot.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.$basename) ?: [];
        }

        $match = $matches[0] ?? null;
        if (! is_string($match) || ! is_file($match)) {
            return null;
        }

        $repoRelative = str_replace('\\', '/', substr($match, strlen(base_path()) + 1));
        $repo = trim((string) config('marketplace.hepsiburada.github_repo', 'kosarteknikservis-pixel/kosarticaret-'), '/');
        $branch = trim((string) config('marketplace.hepsiburada.github_branch', 'main'), '/');
        // Cache-bust: HB aynı URL ile güncellemeyi reddeder.
        $bust = substr(md5($sourceRelative.now()->format('YmdHis')), 0, 8);

        return sprintf(
            'https://cdn.jsdelivr.net/gh/%s@%s/%s?v=%s',
            $repo,
            $branch,
            ltrim($repoRelative, '/'),
            $bust
        );
    }

    private function uploadToCatbox(string $publishedRelative): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($publishedRelative)) {
            throw new RuntimeException('Catbox yükleme: dosya yok '.$publishedRelative);
        }

        $binary = $disk->get($publishedRelative);
        $filename = basename($publishedRelative) ?: 'image.jpg';

        $response = Http::timeout(60)
            ->attach('fileToUpload', $binary, $filename)
            ->post('https://catbox.moe/user/api.php', [
                'reqtype' => 'fileupload',
            ]);

        $url = trim((string) $response->body());
        if (! $response->successful() || ! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Catbox görsel yükleme başarısız: '.mb_substr($url, 0, 200));
        }

        return $url;
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
    private function isPublicImageReachable(string $url): bool
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; KosarHbPreflight/1.0)',
                    'Range' => 'bytes=0-1023',
                ])
                ->get($url);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful() && $response->status() !== 206) {
            return false;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        $body = (string) $response->body();

        return $body !== '' && ($contentType === '' || str_starts_with($contentType, 'image/'));
    }

    private function assertImagesPubliclyReachable(array $attributes): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $key = 'Image'.$i;
            if (! isset($attributes[$key]) || ! is_string($attributes[$key]) || $attributes[$key] === '') {
                continue;
            }

            $url = $attributes[$key];
            if (! $this->isPublicImageReachable($url)) {
                throw new InvalidArgumentException(
                    "Hepsiburada {$key} erişilemedi veya geçerli görsel değil: ".$url
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
