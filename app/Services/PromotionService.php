<?php

namespace App\Services;

use App\Models\Promotion;

class PromotionService
{
    /**
     * @param  list<array{product: \App\Models\Product, quantity: int, line_total: float}>  $lines
     * @return array{discount: float, free_shipping: bool, label: ?string}
     */
    public function autoBenefits(float $cartSubtotal, array $lines): array
    {
        $discount = 0.0;
        $freeShipping = false;
        $label = null;

        $promotions = Promotion::query()->active()->where('auto_apply', true)->get();

        foreach ($promotions as $promo) {
            if ($promo->min_cart && $cartSubtotal < (float) $promo->min_cart) {
                continue;
            }

            match ($promo->type) {
                Promotion::TYPE_PERCENT => $discount += round($cartSubtotal * ((float) $promo->value / 100), 2),
                Promotion::TYPE_FIXED => $discount += min((float) $promo->value, $cartSubtotal),
                Promotion::TYPE_FREE_SHIPPING => null,
                Promotion::TYPE_BUY_X_GET_Y => $discount += $this->buyXGetYDiscount($lines, (int) $promo->buy_qty, (int) $promo->free_qty),
                default => null,
            };

            $label = $promo->name;
        }

        return [
            'discount' => round($discount, 2),
            'free_shipping' => $freeShipping,
            'label' => $label,
        ];
    }

    /**
     * @param  list<array{product: \App\Models\Product, quantity: int, line_total: float}>  $lines
     */
    private function buyXGetYDiscount(array $lines, int $buyQty, int $freeQty): float
    {
        if ($buyQty < 1 || $freeQty < 1) {
            return 0;
        }

        $groupSize = $buyQty + $freeQty;
        $units = [];
        foreach ($lines as $line) {
            $price = (float) $line['product']->price;
            for ($i = 0; $i < $line['quantity']; $i++) {
                $units[] = $price;
            }
        }

        if (count($units) < $groupSize) {
            return 0;
        }

        rsort($units);
        $freeCount = intdiv(count($units), $groupSize) * $freeQty;
        $freeUnits = array_slice($units, -$freeCount);

        return round(array_sum($freeUnits), 2);
    }
}
