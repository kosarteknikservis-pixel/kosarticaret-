<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;

class MarketplaceChannelPricing
{
    public function salePrice(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $overridePrice = null,
    ): float {
        if ($overridePrice !== null && $overridePrice > 0) {
            return round($overridePrice, 2);
        }

        $mode = (string) $channel->setting('price_mode', 'site');
        $base = (float) $product->price;

        return match ($mode) {
            'markup' => round($base * (1 + ((float) $channel->setting('price_markup_percent', 0) / 100)), 2),
            'margin' => $this->marginSalePrice($product, $channel) ?? $base,
            'fixed' => (float) ($listing?->channel_price ?? $base),
            default => $base,
        };
    }

    public function listPrice(
        Product $product,
        MarketplaceChannel $channel,
        ?MarketplaceListing $listing = null,
        ?float $overridePrice = null,
    ): float {
        $sale = $this->salePrice($product, $channel, $listing, $overridePrice);
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
        $qty = (int) floor($available * (1 - ($buffer / 100)));

        // Buffer küçük stoku sıfırlamasın (1 adet varken 0 gönderme).
        if ($available > 0 && $qty < 1) {
            return 1;
        }

        return $qty;
    }

    /**
     * KDV hariç: (maliyet + sabit) / (1 − komisyon% − diğer% − hedef kâr%).
     */
    public function marginSalePrice(Product $product, MarketplaceChannel $channel): ?float
    {
        $cost = $product->cost_price !== null ? (float) $product->cost_price : null;

        if ($cost === null || $cost <= 0) {
            return null;
        }

        $commission = max(0, (float) $channel->setting('commission_percent', 15));
        $other = max(0, (float) $channel->setting('other_fee_percent', 0));
        $targetMargin = max(0, (float) $channel->setting('target_margin_percent', 10));
        $fixedFee = max(0, (float) $channel->setting('fixed_fee', 0));

        $deduction = ($commission + $other + $targetMargin) / 100;

        if ($deduction >= 1) {
            return null;
        }

        return round(($cost + $fixedFee) / (1 - $deduction), 2);
    }

    /**
     * @return array{
     *     sale_price: float|null,
     *     cost: float,
     *     fixed_fee: float,
     *     commission_percent: float,
     *     other_fee_percent: float,
     *     target_margin_percent: float,
     *     estimated_commission: float|null,
     *     estimated_other: float|null,
     *     estimated_profit: float|null,
     *     usable: bool,
     * }
     */
    public function marginBreakdown(Product $product, MarketplaceChannel $channel): array
    {
        $cost = (float) ($product->cost_price ?? 0);
        $commission = max(0, (float) $channel->setting('commission_percent', 15));
        $other = max(0, (float) $channel->setting('other_fee_percent', 0));
        $targetMargin = max(0, (float) $channel->setting('target_margin_percent', 10));
        $fixedFee = max(0, (float) $channel->setting('fixed_fee', 0));
        $sale = $this->marginSalePrice($product, $channel);

        $estimatedCommission = $sale !== null ? round($sale * ($commission / 100), 2) : null;
        $estimatedOther = $sale !== null ? round($sale * ($other / 100), 2) : null;
        $estimatedProfit = $sale !== null
            ? round($sale - $estimatedCommission - $estimatedOther - $fixedFee - $cost, 2)
            : null;

        return [
            'sale_price' => $sale,
            'cost' => $cost,
            'fixed_fee' => $fixedFee,
            'commission_percent' => $commission,
            'other_fee_percent' => $other,
            'target_margin_percent' => $targetMargin,
            'estimated_commission' => $estimatedCommission,
            'estimated_other' => $estimatedOther,
            'estimated_profit' => $estimatedProfit,
            'usable' => $sale !== null && $sale > 0,
        ];
    }
}
