<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SiteSetting;

class WhatsAppOrder
{
    public static function isEnabledForPdp(): bool
    {
        if (SiteSetting::get('pdp_whatsapp_order_enabled', '1') !== '1') {
            return false;
        }

        return self::phone() !== null;
    }

    public static function buttonLabel(): string
    {
        $custom = trim((string) SiteSetting::get('pdp_whatsapp_order_label', ''));

        return $custom !== '' ? $custom : __('shop.whatsapp_order');
    }

    public static function phone(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp')));

        return strlen($digits) >= 10 ? $digits : null;
    }

    public static function message(Product $product, int $qty = 1): string
    {
        $qty = max(1, $qty);
        $unit = number_format((float) $product->price, 2, ',', '.');
        $total = number_format((float) $product->price * $qty, 2, ',', '.');

        $lines = [
            __('shop.whatsapp_order_message_intro'),
            '',
            __('shop.whatsapp_order_product', ['name' => $product->name]),
            __('shop.whatsapp_order_sku', ['sku' => $product->sku]),
            __('shop.whatsapp_order_qty', ['qty' => $qty]),
            __('shop.whatsapp_order_unit_price', ['price' => $unit]),
            __('shop.whatsapp_order_total', ['price' => $total]),
            __('shop.whatsapp_order_link', ['url' => route('products.show', $product)]),
        ];

        return implode("\n", $lines);
    }

    public static function orderUrl(Product $product, int $qty = 1): ?string
    {
        $phone = self::phone();
        if ($phone === null) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(self::message($product, $qty));
    }
}
