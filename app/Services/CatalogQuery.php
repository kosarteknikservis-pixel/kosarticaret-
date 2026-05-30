<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogQuery
{
    /** @return Builder<Product> */
    public static function products(): Builder
    {
        return Product::query()->active();
    }

    /** @param  Builder<Product>  $query */
    public static function apply(Request $request, Builder $query): Builder
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

        return match ($request->string('siralama')->toString()) {
            'fiyat-artan' => $query->orderBy('price'),
            'fiyat-azalan' => $query->orderByDesc('price'),
            'isim' => $query->orderBy('name'),
            default => $query->latest(),
        };
    }
}
