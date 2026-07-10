<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\StoreConfig;

class ProductCardFreeShipping
{
    public static function badgeEnabled(): bool
    {
        return SiteSetting::get('product_card_free_shipping_badge', '1') === '1';
    }

    public static function freeShippingMin(): float
    {
        return app(StoreConfig::class)->freeShippingMin();
    }

    public static function qualifies(Product $product): bool
    {
        if (! self::badgeEnabled()) {
            return false;
        }

        return (float) $product->price >= self::freeShippingMin();
    }
}
