<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    private ?Collection $productsCache = null;

    private ?string $productsCacheSignature = null;

    /** @var list<array{product: Product, quantity: int, line_total: float}>|null */
    private ?array $linesCache = null;

    private ?string $linesCacheSignature = null;

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
        $signature = md5(json_encode($this->items()));
        if ($this->productsCache !== null && $this->productsCacheSignature === $signature) {
            return $this->productsCache;
        }

        $ids = array_keys($this->items());

        $this->productsCache = Product::query()
            ->whereIn('id', $ids)
            ->active()
            ->where('price', '>', 0)
            ->get()
            ->keyBy('id');
        $this->productsCacheSignature = $signature;

        return $this->productsCache;
    }

    /** Satışta olmayan ürünleri oturum sepetinden siler; silinen satır sayısını döner. */
    public function pruneUnavailable(): int
    {
        $cart = $this->items();
        if ($cart === []) {
            return 0;
        }

        $available = $this->products();
        $removed = 0;
        foreach (array_keys($cart) as $id) {
            if (! $available->has($id)) {
                unset($cart[$id]);
                $removed++;
            }
        }

        if ($removed > 0) {
            session(['cart' => $cart]);
        }

        return $removed;
    }

    /**
     * @return list<array{product: Product, quantity: int, line_total: float}>
     */
    public function lines(): array
    {
        $signature = md5(json_encode($this->items()));
        if ($this->linesCache !== null && $this->linesCacheSignature === $signature) {
            return $this->linesCache;
        }

        $lines = [];
        $products = $this->products();
        foreach ($this->items() as $id => $qty) {
            $product = $products->get($id);
            if (! $product) {
                continue;
            }
            $lines[] = [
                'product' => $product,
                'quantity' => $qty,
                'line_total' => round($product->price * $qty, 2),
            ];
        }

        $this->linesCache = $lines;
        $this->linesCacheSignature = $signature;

        return $this->linesCache;
    }

    public function subtotal(): float
    {
        return round(collect($this->lines())->sum('line_total'), 2);
    }

    /** Sepet toplam desi (ürün desi × adet). Desi'si olmayan ürünler 0 sayılır. */
    public function totalDesi(): float
    {
        return round(collect($this->lines())->sum(function (array $line): float {
            $desi = $line['product']->desi();

            return $desi !== null ? ((float) $desi * (int) $line['quantity']) : 0.0;
        }), 2);
    }

    public function clear(): void
    {
        session()->forget('cart');
        session()->forget('coupon_code');
        $this->productsCache = null;
        $this->productsCacheSignature = null;
        $this->linesCache = null;
        $this->linesCacheSignature = null;
    }

    /**
     * Sepete stok üst sınırıyla ekler. Mevcut adet + istenen miktar stoğu aşamaz.
     *
     * @return array{ok: bool, added: int, quantity: int, message: string}
     */
    public function addProduct(Product $product, int $qty = 1): array
    {
        if (! $product->is_active || (float) $product->price <= 0) {
            $cart = $this->items();
            if (isset($cart[$product->id])) {
                unset($cart[$product->id]);
                session(['cart' => $cart]);
            }

            return [
                'ok' => false,
                'added' => 0,
                'quantity' => 0,
                'message' => 'Bu ürün şu an satışta değil.',
            ];
        }

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
