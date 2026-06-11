<?php

namespace App\Services;

use App\Services\StoreConfig;

class CheckoutCalculator
{
    public function __construct(private StoreConfig $store) {}
    public function orderNumber(): string
    {
        $prefix = config('kosar.order_prefix', 'KOS');

        return $prefix.'-'.strtoupper(base_convert((string) now()->timestamp, 10, 36));
    }

    public function shippingCost(float $subtotal, string $method, bool $forceFree = false): float
    {
        $rates = $this->store->shippingRates();
        $fee = (float) ($rates[$method] ?? 0);

        if ($forceFree) {
            return 0.0;
        }

        $freeMin = $this->store->freeShippingMin();
        if ($freeMin > 0 && $subtotal >= $freeMin && $method !== 'hizli') {
            return 0.0;
        }

        return $fee;
    }

    /**
     * @return array{subtotal: float, discount: float, shipping: float, cod_fee: float, vat: float, total: float}
     */
    public function totals(
        float $cartSubtotal,
        float $discount,
        string $shippingMethod,
        string $paymentMethod,
        bool $freeShippingPromo = false,
    ): array {
        $subtotal = max(0, round($cartSubtotal - $discount, 2));
        $shipping = $this->shippingCost($subtotal, $shippingMethod, $freeShippingPromo);
        $codFee = $paymentMethod === 'kapida_odeme' ? $this->store->codFee() : 0;
        $vatBase = $subtotal + $shipping;
        $vat = $this->store->shouldAddVat()
            ? round($vatBase * $this->store->vatRate(), 2)
            : 0.0;
        $total = round($vatBase + $vat + $codFee, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'cod_fee' => $codFee,
            'vat' => $vat,
            'total' => $total,
        ];
    }
}
