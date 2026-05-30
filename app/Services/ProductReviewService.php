<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReview;

class ProductReviewService
{
    public function syncProductRating(Product $product): void
    {
        $stats = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $product->update([
            'rating' => round((float) ($stats->avg_rating ?? 0), 1) ?: 4,
            'review_count' => (int) ($stats->total ?? 0),
        ]);
    }
}
