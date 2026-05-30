<?php

namespace App\Support;

use App\Models\SiteSetting;

final class PaymentMethodSettings
{
    public const CONTEXT_CHECKOUT = 'checkout';

    public const CONTEXT_FOOTER = 'footer';

    /** @return list<string> */
    public static function allIds(): array
    {
        return array_column(config('shipping.payment_methods', []), 'id');
    }

    /** @return list<string> */
    public static function enabledIds(string $context = self::CONTEXT_CHECKOUT): array
    {
        $key = $context === self::CONTEXT_FOOTER
            ? 'payment_footer_enabled'
            : 'payment_checkout_enabled';

        $raw = trim((string) SiteSetting::get($key, ''));
        if ($raw === '') {
            return self::allIds();
        }

        $ids = array_values(array_filter(array_map('trim', explode(',', $raw))));
        $valid = self::allIds();

        return array_values(array_intersect($ids, $valid));
    }

    /** @return array{checkout: list<string>, footer: list<string>} */
    public static function enabledForAdmin(): array
    {
        return [
            'checkout' => self::enabledIds(self::CONTEXT_CHECKOUT),
            'footer' => self::enabledIds(self::CONTEXT_FOOTER),
        ];
    }

    public static function saveEnabled(array $checkout, array $footer): void
    {
        $valid = self::allIds();
        $checkout = array_values(array_intersect($checkout, $valid));
        $footer = array_values(array_intersect($footer, $valid));

        SiteSetting::set('payment_checkout_enabled', implode(',', $checkout));
        SiteSetting::set('payment_footer_enabled', implode(',', $footer));
    }
}
