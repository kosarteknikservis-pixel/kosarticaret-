<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\QuoteRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteRequestController extends Controller
{
    public function __construct(
        private CartService $cart,
        private QuoteRequestService $quotes,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('shop.quote_cart_empty'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:190'],
            'tax_no' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->quotes->submit($request, $data);

        return redirect()->route('cart.index')->with('success', __('shop.quote_success'));
    }
}
