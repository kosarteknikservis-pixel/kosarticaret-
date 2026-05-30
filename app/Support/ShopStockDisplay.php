<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SiteSetting;

class ShopStockDisplay
{
    public static function showQuantity(): bool
    {
        return SiteSetting::get('shop_show_stock_quantity', '0') === '1';
    }

    public static function storefrontLabel(Product $product): ?string
    {
        if (! $product->inStock()) {
            return __('shop.out_of_stock');
        }

        if (! self::showQuantity()) {
            return __('shop.in_stock');
        }

        return __('shop.in_stock_with_qty', [
            'qty' => $product->stock,
            'units' => __('shop.units'),
        ]);
    }
}
