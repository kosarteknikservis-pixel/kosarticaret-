<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\ProductReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private ProductReviewService $reviews) {}

    public function index(): View
    {
        return view('admin.reviews.index', [
            'reviews' => ProductReview::query()->with('product')->latest()->paginate(30),
        ]);
    }

    public function approve(ProductReview $review): RedirectResponse
    {
        $review->update(['approved' => true]);
        $this->reviews->syncProductRating($review->product);

        return back()->with('success', 'Yorum onaylandı.');
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        $product = $review->product;
        $review->delete();
        $this->reviews->syncProductRating($product);

        return back()->with('success', 'Yorum silindi.');
    }
}
