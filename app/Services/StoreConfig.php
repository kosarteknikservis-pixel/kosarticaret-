<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Support\PaymentGatewayConfig;
use App\Support\PaymentMethodSettings;

class StoreConfig
{
    public function setting(string $key, mixed $default = null): ?string
    {
        $fallback = $default !== null ? (string) $default : null;

        return SiteSetting::get($key, $fallback);
    }

    public function freeShippingMin(): float
    {
        return (float) $this->setting('free_shipping_min', config('shipping.free_shipping_min'));
    }

    public function codFee(): float
    {
        return (float) $this->setting('cod_fee', config('shipping.cod_fee'));
    }

    public function vatRate(): float
    {
        return (float) $this->setting('vat_rate', config('shipping.vat_rate'));
    }

    /** @return array<string, float> */
    public function shippingRates(): array
    {
        $defaults = config('shipping.shipping_rates');

        return [
            'standart' => (float) ($this->setting('shipping_rate_standart') ?? $defaults['standart'] ?? 0),
            'hizli' => (float) ($this->setting('shipping_rate_hizli') ?? $defaults['hizli'] ?? 0),
        ];
    }

    /** @return array<int, array<string, string>> */
    public function shippingMethods(): array
    {
        $freeMin = $this->freeShippingMin();
        $autoDesc = number_format($freeMin, 0, ',', '.').' TL üzeri ücretsiz';

        return collect(config('shipping.shipping_methods'))
            ->map(function (array $method) use ($autoDesc) {
                $id = $method['id'];
                $method['name'] = $this->setting("ship_{$id}_name", $method['name']) ?: $method['name'];
                $method['eta'] = $this->setting("ship_{$id}_eta", $method['eta']) ?: $method['eta'];
                $customDesc = $this->setting("ship_{$id}_desc");
                if ($id === 'standart' && ! $customDesc) {
                    $method['desc'] = $autoDesc;
                } else {
                    $method['desc'] = $customDesc ?: ($method['desc'] ?? '');
                }

                return $method;
            })
            ->all();
    }

    /** @return list<string> */
    public function enabledPaymentIds(string $context = PaymentMethodSettings::CONTEXT_CHECKOUT): array
    {
        return PaymentMethodSettings::enabledIds($context);
    }

    /** @return array<int, array<string, string>> */
    public function paymentMethods(string $context = PaymentMethodSettings::CONTEXT_CHECKOUT): array
    {
        $enabled = $this->enabledPaymentIds($context);
        $codFee = number_format($this->codFee(), 2, ',', '.');

        return collect(config('shipping.payment_methods'))
            ->filter(fn (array $method) => in_array($method['id'], $enabled, true))
            ->map(function (array $method) use ($codFee) {
                $id = $method['id'];
                $method['name'] = $this->setting("pay_{$id}_name", $method['name']) ?: $method['name'];
                $desc = $this->setting("pay_{$id}_desc", $method['desc']) ?: $method['desc'];
                $method['desc'] = str_replace('{fee}', $codFee, $desc);
                if ($id === 'kredi_karti' && PaymentGatewayConfig::isLive()) {
                    $gateway = PaymentGatewayConfig::label();
                    if (! str_contains(mb_strtolower($method['desc']), mb_strtolower($gateway))) {
                        $method['desc'] = trim($method['desc'].' — '.$gateway.' 3D Secure');
                    }
                }

                return $method;
            })
            ->values()
            ->all();
    }

    public function vitrin(string $key, string $langDefault): string
    {
        return $this->setting($key, $langDefault) ?: $langDefault;
    }

    /** @return list<string> */
    public function footerPaymentBadges(): array
    {
        $raw = $this->setting('footer_payment_badges', 'Visa, MC, 3D Secure');
        $items = array_filter(array_map('trim', explode(',', (string) $raw)));

        return $items !== [] ? array_values($items) : ['Visa', 'MC', '3D Secure'];
    }
}
