<?php

namespace App\Services;

use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PromotionService
{
    public const KASIM_2026_NAME_PREFIX = 'Kasım 2026 ·';

    /**
     * @return list<array{min: float, discount: float, label: string}>
     */
    public static function kasim2026Tiers(): array
    {
        return [
            ['min' => 1000.0, 'discount' => 150.0, 'label' => '1.000 TL üzeri 150 TL'],
            ['min' => 3000.0, 'discount' => 300.0, 'label' => '3.000 TL üzeri 300 TL'],
            ['min' => 6000.0, 'discount' => 500.0, 'label' => '6.000 TL üzeri 500 TL'],
            ['min' => 10000.0, 'discount' => 800.0, 'label' => '10.000 TL üzeri 800 TL'],
            ['min' => 12000.0, 'discount' => 1100.0, 'label' => '12.000 TL üzeri 1.100 TL'],
        ];
    }

    public static function kasim2026EndsAt(): Carbon
    {
        return Carbon::parse('2026-11-30 23:59:59', 'Europe/Istanbul');
    }

    public function kasim2026IsActive(): bool
    {
        if (now('Europe/Istanbul')->greaterThan(self::kasim2026EndsAt())) {
            return false;
        }

        return Cache::remember('campaign.kasim_2026.active', 300, function (): bool {
            return Promotion::query()
                ->active()
                ->where('auto_apply', true)
                ->where('type', Promotion::TYPE_FIXED)
                ->where('name', 'like', self::KASIM_2026_NAME_PREFIX.'%')
                ->exists();
        });
    }

    /**
     * @param  list<array{product: \App\Models\Product, quantity: int, line_total: float}>  $lines
     * @return array{discount: float, free_shipping: bool, label: ?string}
     */
    public function autoBenefits(float $cartSubtotal, array $lines): array
    {
        $discount = 0.0;
        $freeShipping = false;
        $label = null;
        $bestFixed = null;

        $promotions = Promotion::query()->active()->where('auto_apply', true)->get();

        foreach ($promotions as $promo) {
            if ($promo->min_cart && $cartSubtotal < (float) $promo->min_cart) {
                continue;
            }

            match ($promo->type) {
                Promotion::TYPE_PERCENT => $discount += round($cartSubtotal * ((float) $promo->value / 100), 2),
                Promotion::TYPE_FIXED => $bestFixed = $this->pickBestFixed($bestFixed, $promo, $cartSubtotal),
                Promotion::TYPE_FREE_SHIPPING => $freeShipping = true,
                Promotion::TYPE_BUY_X_GET_Y => $discount += $this->buyXGetYDiscount($lines, (int) $promo->buy_qty, (int) $promo->free_qty),
                default => null,
            };

            if ($promo->type !== Promotion::TYPE_FIXED) {
                $label = $promo->name;
            }
        }

        if ($bestFixed !== null) {
            $discount += $bestFixed['value'];
            $label = $bestFixed['name'];
        }

        return [
            'discount' => round($discount, 2),
            'free_shipping' => $freeShipping,
            'label' => $label,
        ];
    }

    /**
     * @param  array{value: float, min_cart: float, name: string}|null  $current
     * @return array{value: float, min_cart: float, name: string}|null
     */
    private function pickBestFixed(?array $current, Promotion $promo, float $cartSubtotal): ?array
    {
        $candidate = [
            'value' => min((float) $promo->value, $cartSubtotal),
            'min_cart' => (float) ($promo->min_cart ?? 0),
            'name' => (string) $promo->name,
        ];

        if ($current === null) {
            return $candidate;
        }

        if ($candidate['value'] > $current['value']) {
            return $candidate;
        }

        if ($candidate['value'] === $current['value'] && $candidate['min_cart'] > $current['min_cart']) {
            return $candidate;
        }

        return $current;
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
