<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Services\CatalogQuery;
use App\Services\PumpSelection\PumpSpecExtractor;
use Illuminate\Support\Collection;

class RelatedProductService
{
    public function __construct(private PumpSpecExtractor $specs)
    {
    }

    /**
     * @return Collection<int, Product>
     */
    public function for(Product $product, int $limit = 6): Collection
    {
        $sourceSpecs = $this->specs->extract($product);
        $categoryIds = $product->categories->pluck('id')->filter()->values();

        $candidates = CatalogQuery::products()
            ->with(['brand', 'categories'])
            ->where('id', '!=', $product->id)
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query->whereHas(
                    'categories',
                    fn ($categories) => $categories->whereIn('categories.id', $categoryIds)
                ),
                fn ($query) => $query->where('featured', true),
            )
            ->orderByDesc('featured')
            ->orderByDesc('stock')
            ->limit(40)
            ->get();

        if ($candidates->count() < 12) {
            $parentIds = $product->categories
                ->pluck('parent_id')
                ->filter()
                ->unique()
                ->values();

            if ($parentIds->isNotEmpty()) {
                $extra = CatalogQuery::products()
                    ->with(['brand', 'categories'])
                    ->where('id', '!=', $product->id)
                    ->whereNotIn('id', $candidates->pluck('id'))
                    ->whereHas('categories', function ($query) use ($parentIds) {
                        $query->where(function ($inner) use ($parentIds) {
                            $inner->whereIn('categories.parent_id', $parentIds)
                                ->orWhereIn('categories.id', $parentIds);
                        });
                    })
                    ->orderByDesc('featured')
                    ->orderByDesc('stock')
                    ->limit(24)
                    ->get();

                $candidates = $candidates->concat($extra);
            }
        }

        if ($candidates->isEmpty()) {
            return CatalogQuery::products()
                ->with('brand')
                ->where('id', '!=', $product->id)
                ->where('featured', true)
                ->orderByDesc('stock')
                ->limit($limit)
                ->get();
        }

        return $candidates
            ->unique('id')
            ->sortByDesc(fn (Product $candidate) => $this->score($product, $candidate, $sourceSpecs))
            ->take($limit)
            ->values();
    }

    /**
     * @param  array{flow_m3h: ?float, head_m: ?float, power_kw: ?float}  $sourceSpecs
     */
    public function score(Product $source, Product $candidate, array $sourceSpecs): int
    {
        $score = 0;

        if ((int) $candidate->stock > 0) {
            $score += 50;
        }

        if ($candidate->featured) {
            $score += 12;
        }

        $candidateSpecs = $this->specs->extract($candidate);
        $score += $this->proximity($sourceSpecs['flow_m3h'] ?? null, $candidateSpecs['flow_m3h'] ?? null);
        $score += $this->proximity($sourceSpecs['head_m'] ?? null, $candidateSpecs['head_m'] ?? null);
        $score += $this->proximity($sourceSpecs['power_kw'] ?? null, $candidateSpecs['power_kw'] ?? null);

        $sourceTokens = $this->tokens($source->name);
        $candidateTokens = $this->tokens($candidate->name);
        $score += count(array_intersect($sourceTokens, $candidateTokens)) * 4;

        return $score;
    }

    private function proximity(?float $a, ?float $b): int
    {
        if ($a === null || $b === null || $a <= 0.0) {
            return 0;
        }

        $ratio = abs($a - $b) / $a;
        if ($ratio <= 0.15) {
            return 30;
        }
        if ($ratio <= 0.40) {
            return 15;
        }

        return 0;
    }

    /** @return list<string> */
    private function tokens(string $name): array
    {
        $stop = ['ve', 'ile', 'icin', 'için', 'tip', 'tipi', 'pompa', 'pompası', 'pompasi'];

        return collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($name, 'UTF-8')) ?: [])
            ->filter(fn ($token) => is_string($token) && mb_strlen($token) >= 3 && ! in_array($token, $stop, true))
            ->unique()
            ->values()
            ->all();
    }
}
