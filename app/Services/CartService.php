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

    /**
     * Sepete stok üst sınırıyla ekler. Mevcut adet + istenen miktar stoğu aşamaz.
     *
     * @return array{ok: bool, added: int, quantity: int, message: string}
     */
    public function addProduct(Product $product, int $qty = 1): array
    {
        $qty = max(1, $qty);
        $stock = max(0, (int) $product->stock);
        $cart = $this->items();
        $current = (int) ($cart[$product->id] ?? 0);

        if ($stock <= 0) {
            if ($current > 0) {
                unset($cart[$product->id]);
                session(['cart' => $cart]);
            }

            return [
                'ok' => false,
                'added' => 0,
                'quantity' => 0,
                'message' => 'Bu ürün stokta yok.',
            ];
        }

        if ($current > $stock) {
            $cart[$product->id] = $stock;
            session(['cart' => $cart]);
            $current = $stock;
        }

        if ($current >= $stock) {
            return [
                'ok' => false,
                'added' => 0,
                'quantity' => $stock,
                'message' => "Stokta en fazla {$stock} adet var. Sepetinizde zaten {$stock} adet bulunuyor.",
            ];
        }

        $newQty = min($current + $qty, $stock);
        $added = $newQty - $current;
        $cart[$product->id] = $newQty;
        session(['cart' => $cart]);

        $message = $added < $qty
            ? "Stokta en fazla {$stock} adet var. Sepete {$added} adet eklendi."
            : 'Ürün sepete eklendi.';

        return [
            'ok' => true,
            'added' => $added,
            'quantity' => $newQty,
            'message' => $message,
        ];
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
