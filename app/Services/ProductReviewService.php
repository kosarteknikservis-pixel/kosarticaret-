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

        $count = (int) ($stats->total ?? 0);
        $rating = $count > 0 ? round((float) ($stats->avg_rating ?? 0), 1) : 0;

        $product->update([
            'rating' => $rating,
            'review_count' => $count,
        ]);
    }
}
