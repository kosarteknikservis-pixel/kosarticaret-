<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;

class MarketplaceChannelPricing
{
    public function salePrice(Product $product, MarketplaceChannel $channel, ?MarketplaceListing $listing = null): float
    {
        $mode = (string) $channel->setting('price_mode', 'site');
        $base = (float) $product->price;

        return match ($mode) {
            'markup' => round($base * (1 + ((float) $channel->setting('price_markup_percent', 0) / 100)), 2),
            'fixed' => (float) ($listing?->channel_price ?? $base),
            default => $base,
        };
    }

    public function listPrice(Product $product, MarketplaceChannel $channel, ?MarketplaceListing $listing = null): float
    {
        $sale = $this->salePrice($product, $channel, $listing);
        $compare = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;

        if ($compare !== null && $compare > $sale) {
            return $compare;
        }

        return $sale;
    }

    public function stockQuantity(Product $product, MarketplaceChannel $channel, ?MarketplaceListing $listing = null): int
    {
        if ($listing?->channel_stock_limit !== null) {
            return max(0, (int) $listing->channel_stock_limit);
        }

        $buffer = max(0, min(100, (int) $channel->setting('stock_buffer_percent', 5)));
        $available = max(0, (int) $product->stock);

        return (int) floor($available * (1 - ($buffer / 100)));
    }
}
