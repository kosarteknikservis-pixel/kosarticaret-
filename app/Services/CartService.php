<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    /** @return array<int, int> */
    public function items(): array
    {
        return session('cart', []);
    }

    public function count(): int
    {
        return (int) array_sum($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** @return Collection<int, Product> */
    public function products(): Collection
    {
        $ids = array_keys($this->items());

        return Product::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * @return list<array{product: Product, quantity: int, line_total: float}>
     */
    public function lines(): array
    {
        $lines = [];
        foreach ($this->items() as $id => $qty) {
            $product = $this->products()->get($id);
            if (! $product) {
                continue;
            }
            $lines[] = [
                'product' => $product,
                'quantity' => $qty,
                'line_total' => round($product->price * $qty, 2),
            ];
        }

        return $lines;
    }

    public function subtotal(): float
    {
        return round(collect($this->lines())->sum('line_total'), 2);
    }

    public function clear(): void
    {
        session()->forget('cart');
        session()->forget('coupon_code');
    }

    /** @return list<string> */
    public function stockErrors(): array
    {
        $errors = [];
        foreach ($this->lines() as $line) {
            $p = $line['product'];
            if ($p->stock < $line['quantity']) {
                $errors[] = "{$p->name}: stokta en fazla {$p->stock} adet var.";
            }
        }

        return $errors;
    }
}
