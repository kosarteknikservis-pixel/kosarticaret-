<?php

namespace App\Support;

use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use Illuminate\Support\Collection;

final class HomeProductList
{
    public const SOURCES = ['manual', 'category', 'brand', 'featured', 'latest'];

    /** @return Collection<int, Product> */
    public static function resolve(HomeBanner $banner): Collection
    {
        if (! $banner->isProductList()) {
            return collect();
        }

        $limit = max(1, min(24, (int) ($banner->product_limit ?: 4)));
        $source = $banner->product_source ?: 'latest';
        $query = Product::query()->active()->with('brand');

        $products = match ($source) {
            'category' => self::fromCategory($query, $banner->category_id, $limit),
            'brand' => $banner->brand_id
                ? $query->where('brand_id', $banner->brand_id)->orderByDesc('id')->limit($limit)->get()
                : collect(),
            'featured' => $query->where('featured', true)->orderByDesc('id')->limit($limit)->get(),
            'latest' => $query->orderByDesc('id')->limit($limit)->get(),
            'manual' => self::fromManualIds($query, $banner->product_ids ?? [], $limit),
            default => $query->orderByDesc('id')->limit($limit)->get(),
        };

        return $products->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @return Collection<int, Product>
     */
    private static function fromCategory($query, ?int $categoryId, int $limit): Collection
    {
        if (! $categoryId) {
            return collect();
        }

        $category = Category::query()->with('children')->find($categoryId);
        if (! $category) {
            return collect();
        }

        $ids = collect([$category->id])->merge($category->children->pluck('id'));

        return $query
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @param  list<int>|array<int, int>  $ids
     * @return Collection<int, Product>
     */
    private static function fromManualIds($query, array $ids, int $limit): Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }

        $ids = array_slice($ids, 0, $limit);
        $order = array_flip($ids);

        return $query
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $p) => $order[$p->id] ?? 999)
            ->values();
    }

    public static function availableCount(HomeBanner $banner): int
    {
        if (! $banner->isProductList()) {
            return 0;
        }

        return self::resolve($banner)->count();
    }

    public static function sourceLabel(?string $source): string
    {
        return match ($source) {
            'manual' => __('shop.product_list_source_manual'),
            'category' => __('shop.product_list_source_category'),
            'brand' => __('shop.product_list_source_brand'),
            'featured' => __('shop.product_list_source_featured'),
            'latest' => __('shop.product_list_source_latest'),
            default => __('shop.product_list_source_latest'),
        };
    }
}
