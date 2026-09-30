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

        $building = match ($application) {
            'hydrofor_apartment' => ['floors' => (int) ($inputs['floors'] ?? 0), 'apartments' => (int) ($inputs['apartments'] ?? 0), 'soft' => false],
            'hydrofor_villa' => ['floors' => (int) ($inputs['floors'] ?? 0), 'apartments' => (int) ($inputs['bathrooms'] ?? 0), 'soft' => true],
            default => null,
        };

        $scored = $candidates->map(function (Product $product) use ($requiredFlow, $requiredHead, $isFan, $config, $building) {
            $specs = $this->extractor->extract($product);
            $score = $this->scoreProduct($product, $specs, $requiredFlow, $requiredHead, $isFan, $config);
            if ($building !== null && $score['score'] > 0) {
                $score = $this->applyBuildingCapacity($product, $score, $building['floors'], $building['apartments'], $building['soft']);
            }

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
     * Hidrofor adındaki "N Kat M Daire" etiketi üreticinin kapasite beyanıdır:
     * istenenin altındaysa ürün elenir, en yakın uygun kapasite öne çıkar.
     * Villada daire sayısı yerine banyo sayısı yalnızca sıralama için kullanılır ($softApartments).
     *
     * @param  array{score: int, reason: string}  $score
     * @return array{score: int, reason: string}
     */
    private function applyBuildingCapacity(Product $product, array $score, int $floors, int $apartments, bool $softApartments = false): array
    {
        $capacity = self::nameCapacity($product->name);
        if ($capacity['floors'] === null && $capacity['apartments'] === null) {
            return $score;
        }

        $checks = array_filter([
            'floors' => $floors > 0 && $capacity['floors'] !== null ? [$floors, $capacity['floors']] : null,
            'apartments' => $apartments > 0 && $capacity['apartments'] !== null ? [$apartments, $capacity['apartments']] : null,
        ]);

        foreach ($checks as $dimension => [$required, $rated]) {
            if ($rated < $required && ! ($softApartments && $dimension === 'apartments')) {
                return ['score' => 0, 'reason' => $score['reason']];
            }
        }

        $points = $score['score'];
        foreach ($checks as [$required, $rated]) {
            $ratio = $rated / $required;
            $points += match (true) {
                $ratio < 1 => 0,
                $ratio <= 1.2 => 22,
                $ratio <= 1.5 => 18,
                $ratio <= 2.5 => 10,
                $ratio <= 4 => 2,
                default => -10,
            };
        }

        $label = implode(' / ', array_filter([
            $capacity['floors'] !== null ? $capacity['floors'].' kat' : null,
            $capacity['apartments'] !== null ? $capacity['apartments'].' daire' : null,
        ]));

        return [
            'score' => max(1, $points),
            'reason' => 'Üretici kapasitesi: '.$label.' · '.$score['reason'],
        ];
    }

    /**
     * @return array{floors: ?int, apartments: ?int}
     */
    public static function nameCapacity(string $name): array
    {
        $normalized = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $name), 'UTF-8');

        $floors = preg_match('/(\d{1,3})\s*kat(?:l[ıi])?\b/u', $normalized, $m) ? (int) $m[1] : null;
        $apartments = preg_match('/(\d{1,3})\s*daire/u', $normalized, $m) ? (int) $m[1] : null;

        return [
            'floors' => $floors > 0 ? $floors : null,
            'apartments' => $apartments > 0 ? $apartments : null,
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
