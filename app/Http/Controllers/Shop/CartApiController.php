<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AnalyticsTracker;
use App\Services\CartPricingService;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartApiController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CartPricingService $pricing,
    ) {}

    public function summary(): JsonResponse
    {
        $this->cart->pruneUnavailable();

        return response()->json($this->payload());
    }

    public function detail(): JsonResponse
    {
        $this->cart->pruneUnavailable();

        $lines = collect($this->cart->lines())->map(fn ($line) => [
            'slug' => $line['product']->slug,
            'name' => $line['product']->name,
            'quantity' => $line['quantity'],
            'price' => (float) $line['product']->price,
            'line_total' => $line['line_total'],
            'image' => $line['product']->imageUrl('product-thumb'),
            'image_alt' => $line['product']->imageAltText(),
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
        $result = $this->cart->addProduct($product, $qty);

        if ($result['added'] > 0) {
            app(AnalyticsTracker::class)->trackCartAction($request, 'cart_add', $product, $result['added']);
        }
        app(AnalyticsTracker::class)->syncCart($request, $this->cart);

        return response()->json($this->payload($result['message'], $result['ok']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $qty = max(0, (int) $request->input('quantity', 1));
        $cart = session('cart', []);
        $newQty = 0;
        if (! $product->is_active || (float) $product->price <= 0) {
            unset($cart[$product->id]);
        } else {
            $newQty = max(0, min($qty, (int) $product->stock));
            if ($newQty === 0) {
                unset($cart[$product->id]);
            } else {
                $cart[$product->id] = $newQty;
            }
        }
        session(['cart' => $cart]);
        app(AnalyticsTracker::class)->trackCartAction($request, $newQty === 0 ? 'cart_remove' : 'cart_update', $product, $newQty);
        app(AnalyticsTracker::class)->syncCart($request, $this->cart);

        return response()->json($this->payload());
    }

    public function remove(Request $request, Product $product): JsonResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);
        app(AnalyticsTracker::class)->trackCartAction($request, 'cart_remove', $product, 0);
        app(AnalyticsTracker::class)->syncCart($request, $this->cart);

        return response()->json($this->payload('Ürün sepetten kaldırıldı.'));
    }

    /** @return array<string, mixed> */
    private function payload(?string $message = null, bool $ok = true): array
    {
        $breakdown = $this->pricing->breakdown();
        $payable = max(0, round($breakdown['subtotal'] - $breakdown['total_discount'], 2));

        return [
            'ok' => $ok,
            'message' => $message,
            'count' => $this->cart->count(),
            'subtotal' => $breakdown['subtotal'],
            'subtotal_formatted' => number_format($breakdown['subtotal'], 2, ',', '.').' ₺',
            'promotion_discount' => $breakdown['promotion_discount'],
            'promotion_discount_formatted' => number_format($breakdown['promotion_discount'], 2, ',', '.').' ₺',
            'promotion_label' => $breakdown['promotion_label'],
            'payable' => $payable,
            'payable_formatted' => number_format($payable, 2, ',', '.').' ₺',
            'cart_url' => route('cart.index'),
            'checkout_url' => route('checkout.show'),
        ];
    }
}
