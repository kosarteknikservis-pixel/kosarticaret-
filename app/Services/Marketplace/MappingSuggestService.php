<?php

namespace App\Services\Marketplace;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceCategoryMapping;
use App\Models\MarketplaceExternalCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MappingSuggestService
{
    /**
     * @return array{category_id: int, external_id: string, external_name: string, external_path: string|null, score: float}|null
     */
    public function suggestCategory(Category $category, string $channelKey): ?array
    {
        $externals = MarketplaceExternalCategory::query()
            ->where('channel_key', $channelKey)
            ->get();

        if ($externals->isEmpty()) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;
        $localName = $this->normalize($category->name);

        foreach ($externals as $external) {
            $score = $this->similarity($localName, $this->normalize($external->name));

            if ($external->path) {
                $score = max($score, $this->similarity($localName, $this->normalize($external->path)));
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $external;
            }
        }

        if ($best === null || $bestScore < 60) {
            return null;
        }

        return [
            'category_id' => $category->id,
            'external_id' => $best->external_id,
            'external_name' => $best->name,
            'external_path' => $best->path,
            'score' => round($bestScore, 1),
        ];
    }

    /**
     * @return array{brand_id: int, external_id: string, external_name: string, score: float}|null
     */
    public function suggestBrand(Brand $brand, string $channelKey): ?array
    {
        $existingNames = MarketplaceBrandMapping::query()
            ->where('channel_key', $channelKey)
            ->whereNotNull('external_brand_name')
            ->pluck('external_brand_name');

        $bestName = null;
        $bestScore = 0.0;
        $localName = $this->normalize($brand->name);

        foreach ($existingNames as $externalName) {
            $score = $this->similarity($localName, $this->normalize((string) $externalName));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestName = (string) $externalName;
            }
        }

        if ($bestName === null || $bestScore < 85) {
            return [
                'brand_id' => $brand->id,
                'external_id' => Str::slug($brand->name),
                'external_name' => $brand->name,
                'score' => 100.0,
            ];
        }

        $existing = MarketplaceBrandMapping::query()
            ->where('channel_key', $channelKey)
            ->where('external_brand_name', $bestName)
            ->first();

        return [
            'brand_id' => $brand->id,
            'external_id' => $existing?->external_brand_id ?? Str::slug($bestName),
            'external_name' => $bestName,
            'score' => round($bestScore, 1),
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array{category_id: int, external_id: string, external_name: string, external_path: string|null, score: float}>
     */
    public function suggestCategories(Collection $categories, string $channelKey): array
    {
        $suggestions = [];

        foreach ($categories as $category) {
            $suggestion = $this->suggestCategory($category, $channelKey);
            if ($suggestion !== null) {
                $suggestions[] = $suggestion;
            }
        }

        return $suggestions;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/[^a-z0-9\s]+/', ' ', $value) ?? $value;

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function similarity(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0;
        }

        similar_text($left, $right, $percent);

        return (float) $percent;
    }
}
