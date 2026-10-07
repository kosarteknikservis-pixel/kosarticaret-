<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Monofaze listesini ürünün gerçek kategorisine göre dizer.
 * Başlık kategori adıdır; uydurma grup cümlesi yok.
 */
class CollectionTypeGroups
{
    /** @var array<string, int> */
    private const ORDER = [
        'derin-kuyu-dalgic-pompa' => 10,
        'keson-kuyu-pompa' => 20,
        'foseptik-dalgic-pompa' => 30,
        'bicakli-dalgic-pompa' => 40,
        'drenaj-dalgic-pompa' => 50,
        'paslanmaz-drenaj-dalgic-pompa' => 55,
        'kirli-su-dalgic-pompa' => 60,
        'yagmur-suyu-tahliye-pompasi' => 70,
        'temiz-su-dalgic-pompasi' => 80,
    ];

    /**
     * Ürün birden fazla tipteyse daha dar olan başlık seçilir.
     *
     * @var array<string, int>
     */
    private const SPECIFIC = [
        'keson-kuyu-pompa' => 1,
        'derin-kuyu-dalgic-pompa' => 2,
        'bicakli-dalgic-pompa' => 1,
        'foseptik-dalgic-pompa' => 2,
        'paslanmaz-drenaj-dalgic-pompa' => 1,
        'kirli-su-dalgic-pompa' => 1,
        'yagmur-suyu-tahliye-pompasi' => 1,
        'drenaj-dalgic-pompa' => 2,
        'temiz-su-dalgic-pompasi' => 1,
    ];

    /** @var list<string> */
    private const PARENTS = ['su-pompalari', 'dalgic-pompalar'];

    public static function applies(Collection $collection): bool
    {
        $rules = $collection->rules ?? [];

        return ($rules['phase'] ?? null) === 'monofaze' && ! array_key_exists('motor_hp', $rules);
    }

    public function heading(Product $product): ?Category
    {
        $known = $product->categories->filter(fn (Category $category) => isset(self::ORDER[$category->slug]));
        if ($known->isNotEmpty()) {
            return $known
                ->sortBy(fn (Category $category) => sprintf('%02d-%03d-%s', self::SPECIFIC[$category->slug] ?? 9, self::ORDER[$category->slug], $category->slug))
                ->first();
        }

        $fallback = $product->categories->first(
            fn (Category $category) => ! in_array($category->slug, self::PARENTS, true)
        );
        if ($fallback) {
            return $fallback;
        }

        return $product->categories->first(
            fn (Category $category) => $category->slug === 'dalgic-pompalar'
        ) ?? $product->categories->first();
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return SupportCollection<int, Product>
     */
    public function sort(SupportCollection $products, Request $request): SupportCollection
    {
        $within = match ($request->string('siralama')->toString()) {
            'fiyat-artan' => fn (Product $product) => sprintf('%012.2f', (float) $product->price),
            'fiyat-azalan' => fn (Product $product) => sprintf('%012.2f', 99999999 - (float) $product->price),
            default => fn (Product $product) => mb_strtolower($product->name),
        };

        return $products->sortBy(function (Product $product) use ($within) {
            $category = $this->heading($product);
            $order = $category && isset(self::ORDER[$category->slug]) ? self::ORDER[$category->slug] : 500;

            return sprintf('%03d-%s-%s', $order, mb_strtolower($category?->name ?? ''), $within($product));
        })->values();
    }

    /**
     * @param  iterable<int, Product>  $products
     * @return list<array{name: string, products: list<Product>}>
     */
    public function groups(iterable $products): array
    {
        $groups = [];
        foreach ($products as $product) {
            $category = $this->heading($product);
            $key = $category?->id ?? 0;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $category?->name ?? '',
                    'products' => [],
                ];
            }
            $groups[$key]['products'][] = $product;
        }

        return array_values($groups);
    }
}
