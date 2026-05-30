<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartApiController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function summary(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function detail(): JsonResponse
    {
        $lines = collect($this->cart->lines())->map(fn ($line) => [
            'slug' => $line['product']->slug,
            'name' => $line['product']->name,
            'quantity' => $line['quantity'],
            'price' => (float) $line['product']->price,
            'line_total' => $line['line_total'],
            'image' => $line['product']->imageUrl(),
            'url' => route('products.show', $line['product']),
        ])->values();

        return response()->json([
            ...$this->payload(),
            'lines' => $lines,
            'empty' => $lines->isEmpty(),
        ]);
    }

    public function add(Request $request, Product $product): JsonResponse
    {
        $qty = max(1, (int) $request->input('quantity', 1));
        $cart = session('cart', []);
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $qty;
        session(['cart' => $cart]);

        return response()->json($this->payload('Ürün sepete eklendi.'));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $qty = max(0, (int) $request->input('quantity', 1));
        $cart = session('cart', []);
        if ($qty === 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = min($qty, $product->stock);
        }
        session(['cart' => $cart]);

        return response()->json($this->payload());
    }

    public function remove(Product $product): JsonResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return response()->json($this->payload('Ürün sepetten kaldırıldı.'));
    }

    /** @return array<string, mixed> */
    private function payload(?string $message = null): array
    {
        return [
            'ok' => true,
            'message' => $message,
            'count' => $this->cart->count(),
            'subtotal' => $this->cart->subtotal(),
            'subtotal_formatted' => number_format($this->cart->subtotal(), 2, ',', '.').' ₺',
            'cart_url' => route('cart.index'),
            'checkout_url' => route('checkout.show'),
        ];
    }
}
