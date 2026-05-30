<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class FavoriteService
{
    /** @return list<int> */
    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', session('favorites', []))));
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function toggle(int $productId): bool
    {
        $ids = $this->ids();
        if (in_array($productId, $ids, true)) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $productId));
            $added = false;
        } else {
            $ids[] = $productId;
            $added = true;
        }
        session(['favorites' => $ids]);

        return $added;
    }

    /** @return Collection<int, Product> */
    public function products(): Collection
    {
        $ids = $this->ids();
        if ($ids === []) {
            return collect();
        }

        return Product::query()->whereIn('id', $ids)->with('brand')->get();
    }
}
