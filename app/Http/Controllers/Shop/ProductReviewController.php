<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\ContactFormSpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $spam = ContactFormSpamGuard::assess($request, 'review');
        ContactFormSpamGuard::clearFormSession('review');

        if ($spam['blocked']) {
            return $this->spamResponse($product, $spam);
        }

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $duplicate = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('email', $data['email'])
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($duplicate) {
            return redirect()
                ->to(route('products.show', $product).'#yorumlar')
                ->withInput()
                ->withErrors(['spam' => 'Bu ürün için son 24 saat içinde zaten yorum gönderdiniz.']);
        }

        ProductReview::query()->create([
            ...$data,
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'approved' => false,
        ]);

        return redirect()
            ->to(route('products.show', $product).'#yorumlar')
            ->with('success', __('shop.review_submitted'));
    }

    /** @param  array{blocked: bool, reason: string|null, silent: bool, message: string|null}  $spam */
    private function spamResponse(Product $product, array $spam): RedirectResponse
    {
        if ($spam['silent']) {
            return redirect()
                ->to(route('products.show', $product).'#yorumlar')
                ->with('success', __('shop.review_submitted'));
        }

        return redirect()
            ->to(route('products.show', $product).'#yorumlar')
            ->withInput()
            ->withErrors(['spam' => $spam['message'] ?? 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.']);
    }
}
