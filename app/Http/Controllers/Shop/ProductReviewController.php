<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

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
}
