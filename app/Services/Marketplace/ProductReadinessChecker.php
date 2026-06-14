<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductReadinessChecker
{
    /**
     * @return array{score: int, ready: bool, checks: list<array{key: string, label: string, passed: bool, message: string|null}>}
     */
    public function evaluate(Product $product): array
    {
        $product->loadMissing(['brand', 'categories', 'images']);

        $checks = [
            $this->check($product->is_active, 'active', 'Yayında', 'Ürün pasif.'),
            $this->check($product->marketplace_enabled, 'marketplace_enabled', 'Pazaryeri açık', 'Pazaryeri gönderimi kapalı.'),
            $this->check(filled($product->sku), 'sku', 'SKU', 'SKU tanımlı değil.'),
            $this->check(filled($product->barcode), 'barcode', 'Barkod (EAN/GTIN)', 'Barkod zorunlu.'),
            $this->check(filled($product->name), 'name', 'Ürün adı', 'Ürün adı boş.'),
            $this->check((float) $product->price > 0, 'price', 'Fiyat', 'Geçerli fiyat girilmedi.'),
            $this->check($product->brand_id !== null, 'brand', 'Marka', 'Marka seçilmedi.'),
            $this->check($product->categories->isNotEmpty(), 'category', 'Kategori', 'En az bir kategori gerekli.'),
            $this->check($product->imageUrl() !== null, 'image', 'Kapak görseli', 'Kapak görseli yok.'),
            $this->check($this->hasDescription($product), 'description', 'Açıklama', 'Yeterli açıklama yok.'),
            $this->check($this->hasLogistics($product), 'logistics', 'Desi / ağırlık', 'Ağırlık veya en/boy/yükseklik girilmeli.'),
        ];

        $passed = collect($checks)->where('passed', true)->count();
        $score = (int) round(($passed / max(count($checks), 1)) * 100);

        return [
            'score' => $score,
            'ready' => $passed === count($checks),
            'checks' => $checks,
        ];
    }

    /** @return list<string> */
    public function missingLabels(Product $product): array
    {
        return collect($this->evaluate($product)['checks'])
            ->reject(fn (array $check) => $check['passed'])
            ->pluck('label')
            ->values()
            ->all();
    }

    public function categoryMapped(Product $product, string $channelKey): bool
    {
        $categoryIds = $product->categories()->pluck('categories.id');

        if ($categoryIds->isEmpty()) {
            return false;
        }

        return MarketplaceCategoryMapping::query()
            ->where('channel_key', $channelKey)
            ->whereIn('category_id', $categoryIds)
            ->exists();
    }

    public function brandMapped(Product $product, string $channelKey): bool
    {
        if (! $product->brand_id) {
            return false;
        }

        return MarketplaceBrandMapping::query()
            ->where('channel_key', $channelKey)
            ->where('brand_id', $product->brand_id)
            ->exists();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array{ready: int, not_ready: int}
     */
    public function summarize(Collection $products): array
    {
        $ready = 0;

        foreach ($products as $product) {
            if ($this->evaluate($product)['ready']) {
                $ready++;
            }
        }

        return [
            'ready' => $ready,
            'not_ready' => max($products->count() - $ready, 0),
        ];
    }

    private function check(bool $passed, string $key, string $label, string $failMessage): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'passed' => $passed,
            'message' => $passed ? null : $failMessage,
        ];
    }

    private function hasDescription(Product $product): bool
    {
        $min = (int) config('marketplace.readiness.min_description_length', 50);
        $text = strip_tags((string) ($product->short_description ?: $product->description ?: ''));

        return mb_strlen(trim($text)) >= $min;
    }

    private function hasLogistics(Product $product): bool
    {
        if ($product->weight_kg !== null && (float) $product->weight_kg > 0) {
            return true;
        }

        return $product->width_cm && $product->height_cm && $product->depth_cm;
    }
}
