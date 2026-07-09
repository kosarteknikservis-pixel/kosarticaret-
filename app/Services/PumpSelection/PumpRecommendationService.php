<?php

namespace App\Services\PumpSelection;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class PumpRecommendationService
{
    public function __construct(
        private PumpRequirementCalculator $calculator,
        private PumpSpecExtractor $extractor,
    ) {}

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{
     *     requirements: array<string, mixed>,
     *     products: list<array<string, mixed>>,
     *     category_url: ?string
     * }
     */
    public function recommend(string $application, array $inputs): array
    {
        $requirements = $this->calculator->calculate($application, $inputs);
        $config = config("pump_selector.applications.{$application}");

        if ($config === null) {
            throw new \InvalidArgumentException('Geçersiz uygulama tipi.');
        }

        $categoryIds = $this->resolveCategoryIds($config['category_slugs'] ?? []);
        $candidates = $this->loadCandidates($categoryIds, $config);

        $requiredFlow = (float) $requirements['flow_m3h'];
        $requiredHead = (float) $requirements['head_m'];
        $isFan = $application === 'industrial_fan';

        $scored = $candidates->map(function (Product $product) use ($requiredFlow, $requiredHead, $isFan, $config) {
            $specs = $this->extractor->extract($product);
            $score = $this->scoreProduct($product, $specs, $requiredFlow, $requiredHead, $isFan, $config);

            return [
                'product' => $product,
                'specs' => $specs,
                'score' => $score['score'],
                'match_reason' => $score['reason'],
            ];
        })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->take((int) config('pump_selector.limits.max_recommendations', 8))
            ->values();

        $primaryCategory = Category::query()
            ->whereIn('slug', $config['category_slugs'] ?? [])
            ->where('active', true)
            ->orderBy('sort_order')
            ->first();

        return [
            'requirements' => $requirements,
            'products' => $scored->map(fn (array $row) => $this->formatProduct($row))->all(),
            'category_url' => $primaryCategory?->storefrontUrl(),
        ];
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function resolveCategoryIds(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Category::query()
            ->where('active', true)
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  array<string, mixed>  $config
     * @return Collection<int, Product>
     */
    private function loadCandidates(array $categoryIds, array $config): Collection
    {
        $query = Product::query()
            ->active()
            ->with(['brand:id,name,slug', 'categories:id,slug,name'])
            ->select(['id', 'slug', 'sku', 'name', 'short_description', 'description', 'price', 'stock', 'image', 'specs', 'featured', 'brand_id']);

        if ($categoryIds !== []) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        }

        $products = $query->orderByDesc('featured')->orderByDesc('stock')->limit(400)->get();

        return $products->filter(function (Product $product) use ($config) {
            $name = mb_strtolower($product->name, 'UTF-8');

            foreach ($config['exclude_keywords'] ?? [] as $exclude) {
                if (str_contains($name, mb_strtolower($exclude, 'UTF-8'))) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  array{flow_m3h: ?float, head_m: ?float, power_kw: ?float}  $specs
     * @param  array<string, mixed>  $config
     * @return array{score: int, reason: string}
     */
    private function scoreProduct(
        Product $product,
        array $specs,
        float $requiredFlow,
        float $requiredHead,
        bool $isFan,
        array $config,
    ): array {
        $score = 0;
        $reasons = [];

        $name = mb_strtolower($product->name, 'UTF-8');
        foreach ($config['name_keywords'] ?? [] as $keyword) {
            if (str_contains($name, mb_strtolower($keyword, 'UTF-8'))) {
                $score += 8;
                break;
            }
        }

        if ($product->stock > 0) {
            $score += 12;
            $reasons[] = 'Stokta';
        }

        if ($product->featured) {
            $score += 4;
        }

        $flowLow = (float) config('pump_selector.limits.flow_tolerance_low', 0.85);
        $headLow = (float) config('pump_selector.limits.head_tolerance_low', 0.90);
        $oversizeRatio = (float) config('pump_selector.limits.oversize_penalty_ratio', 2.5);

        if ($isFan) {
            $score += 25;
            $reasons[] = 'Havalandırma kategorisi';

            return [
                'score' => $score,
                'reason' => implode(' · ', array_slice($reasons, 0, 3)) ?: 'Kategori eşleşmesi',
            ];
        }

        $productFlow = $specs['flow_m3h'];
        $productHead = $specs['head_m'];

        if ($productFlow !== null && $requiredFlow > 0) {
            if ($productFlow >= $requiredFlow * $flowLow) {
                $score += 35;
                $reasons[] = sprintf('Debi %.1f m³/s', $productFlow);

                if ($productFlow > $requiredFlow * $oversizeRatio) {
                    $score -= 8;
                }
            } elseif ($productFlow >= $requiredFlow * 0.65) {
                $score += 12;
                $reasons[] = 'Debi sınırda uygun';
            }
        }

        if ($productHead !== null && $requiredHead > 0) {
            if ($productHead >= $requiredHead * $headLow) {
                $score += 35;
                $reasons[] = sprintf('Basma %.0f m', $productHead);

                if ($productHead > $requiredHead * $oversizeRatio) {
                    $score -= 6;
                }
            } elseif ($productHead >= $requiredHead * 0.70) {
                $score += 10;
                $reasons[] = 'Basma sınırda uygun';
            }
        }

        if ($productFlow === null && $productHead === null) {
            $score += 15;
            $reasons[] = 'Kategori ve uygulama eşleşmesi';
        } elseif ($productFlow !== null && $productHead !== null
            && $productFlow >= $requiredFlow * $flowLow
            && $productHead >= $requiredHead * $headLow) {
            $score += 10;
            $reasons[] = 'Hidrolik uyum yüksek';
        }

        if ($specs['power_kw'] !== null) {
            $score += 2;
        }

        return [
            'score' => max(0, $score),
            'reason' => implode(' · ', array_slice($reasons, 0, 3)) ?: 'Önerilen model',
        ];
    }

    /**
     * @param  array{product: Product, specs: array<string, mixed>, score: int, match_reason: string}  $row
     * @return array<string, mixed>
     */
    private function formatProduct(array $row): array
    {
        /** @var Product $product */
        $product = $row['product'];
        $specs = $row['specs'];

        $specSummary = array_filter([
            $specs['flow_m3h'] ? sprintf('Debi: %.1f m³/s', $specs['flow_m3h']) : null,
            $specs['head_m'] ? sprintf('Basma: %.0f m', $specs['head_m']) : null,
            $specs['power_kw'] ? sprintf('Güç: %.2f kW', $specs['power_kw']) : null,
        ]);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'url' => route('products.show', $product),
            'image' => $product->imageUrl('product-card') ?? $product->imageUrl(),
            'image_alt' => $product->imageAltText(),
            'price' => (float) $product->price,
            'price_formatted' => number_format((float) $product->price, 2, ',', '.').' ₺',
            'in_stock' => $product->stock > 0,
            'brand' => $product->brand?->name,
            'spec_summary' => implode(' · ', $specSummary),
            'match_score' => $row['score'],
            'match_reason' => $row['match_reason'],
        ];
    }
}
