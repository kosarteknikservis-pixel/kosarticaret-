<?php

namespace App\Services;

use App\Models\Order;
use App\Services\StoreConfig;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutCalculator
{
    public function __construct(private StoreConfig $store) {}
    public function orderNumber(): string
    {
        $prefix = config('kosar.order_prefix', 'KOS');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $sonek = strtoupper(Str::random(4));
            $number = $prefix.'-'.strtoupper(base_convert((string) now()->timestamp, 10, 36)).$sonek;

            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('Sipariş numarası üretilemedi.');
    }

    public function shippingCost(float $subtotal, string $method, bool $forceFree = false, float $cartDesi = 0.0): float
    {
        $rates = $this->store->shippingRates();
        $fee = (float) ($rates[$method] ?? 0);
        $desiRate = $this->store->desiRate();

        if ($desiRate > 0 && $cartDesi > 0) {
            $fee += (float) (ceil($cartDesi) * $desiRate);
        }

        if ($forceFree) {
            return 0.0;
        }

        $freeMin = $this->store->freeShippingMin();
        if ($freeMin > 0 && $subtotal >= $freeMin && $method !== 'hizli') {
            return 0.0;
        }

        return round($fee, 2);
    }

    /**
     * @return array{subtotal: float, discount: float, havale_discount: float, shipping: float, cod_fee: float, vat: float, total: float}
     */
    public function totals(
        float $cartSubtotal,
        float $discount,
        string $shippingMethod,
        string $paymentMethod,
        bool $freeShippingPromo = false,
        float $cartDesi = 0.0,
    ): array {
        $subtotal = max(0, round($cartSubtotal - $discount, 2));
        $shipping = $this->shippingCost($subtotal, $shippingMethod, $freeShippingPromo, $cartDesi);
        $havaleDiscount = $paymentMethod === 'havale' ? $this->store->havaleDiscountAmount($subtotal) : 0.0;
        $payableGoods = max(0, round($subtotal - $havaleDiscount, 2));
        $codFee = $paymentMethod === 'kapida_odeme' ? $this->store->codFee() : 0;
        $vatBase = $payableGoods + $shipping;
        $vat = $this->store->shouldAddVat()
            ? round($vatBase * $this->store->vatRate(), 2)
            : 0.0;
        $total = round($vatBase + $vat + $codFee, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'havale_discount' => $havaleDiscount,
            'shipping' => $shipping,
            'cod_fee' => $codFee,
            'vat' => $vat,
            'total' => $total,
        ];
    }
}
