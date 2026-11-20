<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogCategoryPins;
use App\Support\CatalogSpecFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class CatalogQuery
{
    /** @return Builder<Product> */
    public static function products(): Builder
    {
        return Product::query()->active()->catalogListed();
    }

    /**
     * @param  Builder<Product>|Relation  $query
     * @param  Category|null  $category  Verilirse ve varsayılan sıralamadaysa kategori pin’leri uygulanır.
     */
    public static function apply(Request $request, Builder|Relation $query, ?Category $category = null): Builder|Relation
    {
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('marka')) {
            $query->whereHas('brand', fn ($b) => $b->where('slug', $request->string('marka')));
        }

        if ($request->boolean('stokta')) {
            $query->where('stock', '>', 0);
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->input('min'));
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->input('max'));
        }

        CatalogSpecFilters::apply($request, $query);

        $siralama = $request->string('siralama')->toString();

        return match ($siralama) {
            'fiyat-artan' => $query->orderBy('price')->orderBy('id'),
            'fiyat-azalan' => $query->orderByDesc('price')->orderBy('id'),
            'isim' => $query->orderBy('name')->orderBy('id'),
            default => self::applyDefaultSort($query, $category, $siralama),
        };
    }

    /** @param  Builder<Product>|Relation  $query */
    private static function applyDefaultSort(Builder|Relation $query, ?Category $category, string $siralama): Builder|Relation
    {
        if ($category !== null && CatalogCategoryPins::appliesToDefaultSort($category, $siralama)) {
            $pinIds = self::resolvePinIds($query, CatalogCategoryPins::skuOrderFor($category));
            if ($pinIds !== []) {
                $idList = implode(',', array_map('intval', $pinIds));
                // SQLite’ta FIELD yok; CASE id WHEN … (MySQL + SQLite).
                $pinOrderParts = [];
                foreach ($pinIds as $i => $id) {
                    $pinOrderParts[] = 'WHEN '.(int) $id.' THEN '.$i;
                }
                $pinOrderSql = 'CASE id '.implode(' ', $pinOrderParts).' ELSE '.count($pinIds).' END';

                // Stoktaki pinli → diğer stoktakiler → stoksuz (stoksuz pin, stoktakilerin önüne geçmez).
                return $query
                    ->orderByRaw('CASE WHEN id IN ('.$idList.') AND stock > 0 THEN 0 WHEN stock > 0 THEN 1 ELSE 2 END')
                    ->orderByRaw($pinOrderSql)
                    ->orderByDesc('id');
            }
        }

        return $query
            ->orderByDesc('featured')
            ->orderByRaw('CASE WHEN stock > 0 THEN 1 ELSE 0 END DESC')
            ->latest('id');
    }

    /**
     * @param  Builder<Product>|Relation  $query
     * @param  list<string>  $skus
     * @return list<int>
     */
    private static function resolvePinIds(Builder|Relation $query, array $skus): array
    {
        $rows = (clone $query)->whereIn('sku', $skus)->get(['id', 'sku']);
        $bySku = [];
        foreach ($rows as $row) {
            $bySku[mb_strtolower((string) $row->sku)] = (int) $row->id;
        }

        $ids = [];
        foreach ($skus as $sku) {
            $id = $bySku[mb_strtolower($sku)] ?? null;
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  list<int>  $pinIds
     */
    private static function pinOrderCaseSql(array $pinIds): string
    {
        $parts = [];
        foreach (array_values($pinIds) as $index => $id) {
            $parts[] = 'WHEN '.(int) $id.' THEN '.$index;
        }

        return 'CASE id '.implode(' ', $parts).' ELSE '.count($pinIds).' END';
    }
}
