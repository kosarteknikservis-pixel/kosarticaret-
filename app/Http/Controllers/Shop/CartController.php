<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\AnalyticsTracker;
use App\Services\CartService;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(): View
    {
        return view('shop.cart', [
            'menuCategories' => Category::menu()->get(),
            'lines' => $this->cart->lines(),
            'subtotal' => $this->cart->subtotal(),
            'robots' => Seo::ROBOTS_NOINDEX,
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        $qty = max(1, (int) $request->input('quantity', 1));
        $result = $this->cart->addProduct($product, $qty);

        if ($result['added'] > 0) {
            app(AnalyticsTracker::class)->trackCartAction($request, 'cart_add', $product, $result['added']);
        }
        app(AnalyticsTracker::class)->syncCart($request, $this->cart);

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $qty = max(0, (int) $request->input('quantity', 1));
        $cart = session('cart', []);
        if ($qty === 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = min($qty, $product->stock);
        }
        session(['cart' => $cart]);
        app(AnalyticsTracker::class)->trackCartAction($request, $qty === 0 ? 'cart_remove' : 'cart_update', $product, $qty);
        app(AnalyticsTracker::class)->syncCart($request, $this->cart);

        return redirect()->route('cart.index');
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);
        app(AnalyticsTracker::class)->trackCartAction($request, 'cart_remove', $product, 0);
        app(AnalyticsTracker::class)->syncCart($request, $this->cart);

        return redirect()->route('cart.index');
    }
}
