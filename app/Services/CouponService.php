<?php

namespace App\Services;

use App\Models\Coupon;

class CouponService
{
    public function appliedCode(): ?string
    {
        return session('coupon_code');
    }

    public function apply(string $code): array
    {
        $coupon = $this->findValid($code);
        if (! $coupon) {
            return ['ok' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon.'];
        }

        session(['coupon_code' => $coupon->code]);

        return ['ok' => true, 'message' => 'Kupon uygulandı.', 'coupon' => $coupon];
    }

    public function remove(): void
    {
        session()->forget('coupon_code');
    }

    public function discountAmount(float $cartSubtotal): float
    {
        $coupon = $this->findValid($this->appliedCode() ?? '');
        if (! $coupon) {
            return 0;
        }

        if ($coupon->min_amount && $cartSubtotal < (float) $coupon->min_amount) {
            return 0;
        }

        if ($coupon->type === 'fixed' && $coupon->fixed_amount) {
            return min((float) $coupon->fixed_amount, $cartSubtotal);
        }

        return round($cartSubtotal * ($coupon->percent / 100), 2);
    }

    public function findValid(?string $code): ?Coupon
    {
        if (! $code) {
            return null;
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->where('active', true)
            ->first();

        if (! $coupon) {
            return null;
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return null;
        }

        return $coupon;
    }
}
