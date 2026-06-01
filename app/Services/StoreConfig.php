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

    public function shouldAddVat(): bool
    {
        return $this->setting('checkout_add_vat', '0') === '1';
    }

    /** @return array<string, float> */
    public function shippingRates(): array
    {
        return collect($this->shippingMethods(true))
            ->mapWithKeys(fn (array $method) => [$method['id'] => (float) ($method['fee'] ?? 0)])
            ->all();
    }

    /** @return array<int, array{id: string, name: string, desc: string, eta: string, fee: float, active: bool}> */
    public function shippingMethods(bool $includeInactive = false): array
    {
        $methods = $this->storedShippingMethods();

        if (! $includeInactive) {
            $methods = array_values(array_filter($methods, fn (array $method) => $method['active']));
        }

        return $methods;
    }

    /** @return array<int, array{id: string, name: string, desc: string, eta: string, fee: float, active: bool}> */
    public function storedShippingMethods(): array
    {
        $freeMin = $this->freeShippingMin();
        $autoDesc = number_format($freeMin, 0, ',', '.').' TL üzeri ücretsiz';
        $raw = $this->setting('shipping_methods_json');
        $decoded = $raw ? json_decode($raw, true) : null;

        if (! is_array($decoded)) {
            $decoded = config('shipping.shipping_methods');
        }

        return collect($decoded)
            ->map(function (array $method) use ($autoDesc) {
                $id = $method['id'];
                $desc = trim((string) ($method['desc'] ?? ''));

                return [
                    'id' => $id,
                    'name' => trim((string) ($method['name'] ?? $id)) ?: $id,
                    'desc' => $desc !== '' ? $desc : ($id === 'standart' ? $autoDesc : ''),
                    'eta' => trim((string) ($method['eta'] ?? '')),
                    'fee' => (float) ($method['fee'] ?? config("shipping.shipping_rates.{$id}", 0)),
                    'active' => array_key_exists('active', $method) ? (bool) $method['active'] : true,
                ];
            })
            ->filter(fn (array $method) => $method['id'] !== '' && $method['name'] !== '')
            ->values()
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
                    $method['desc'] = "{$gateway} ile güvenli 3D Secure ödeme";
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
