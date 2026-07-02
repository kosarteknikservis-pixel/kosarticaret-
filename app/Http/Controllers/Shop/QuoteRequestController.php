<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\QuoteRequestService;
use App\Support\ContactFormSpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $spam = ContactFormSpamGuard::assess($request, 'quote');
        ContactFormSpamGuard::clearFormSession('quote');

        if ($spam['blocked']) {
            if ($spam['silent']) {
                Log::info('quote request spam blocked', [
                    'reason' => $spam['reason'],
                    'ip' => $request->ip(),
                ]);

                return redirect()->route('cart.index')->with('success', __('shop.quote_success'));
            }

            return redirect()
                ->route('cart.index')
                ->withInput()
                ->withErrors(['spam' => $spam['message'] ?? 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:190'],
            'tax_no' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $contentSpam = ContactFormSpamGuard::assessContent('quote', $data);
        if ($contentSpam['blocked']) {
            Log::info('quote request content blocked', [
                'reason' => $contentSpam['reason'],
                'ip' => $request->ip(),
            ]);

            return redirect()->route('cart.index')->with('success', __('shop.quote_success'));
        }

        $this->quotes->submit($request, $data);

        return redirect()->route('cart.index')->with('success', __('shop.quote_success'));
    }
}
