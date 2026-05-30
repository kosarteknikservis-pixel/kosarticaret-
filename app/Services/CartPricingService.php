<?php

namespace App\Services;

class CartPricingService
{
    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
        private PromotionService $promotions,
    ) {}

    /**
     * @return array{
     *   subtotal: float,
     *   coupon_discount: float,
     *   promotion_discount: float,
     *   total_discount: float,
     *   free_shipping: bool,
     *   promotion_label: ?string,
     *   coupon_code: ?string
     * }
     */
    public function breakdown(): array
    {
        $lines = $this->cart->lines();
        $subtotal = $this->cart->subtotal();
        $couponDiscount = $this->coupons->discountAmount($subtotal);
        $promo = $this->promotions->autoBenefits($subtotal, $lines);

        return [
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'promotion_discount' => $promo['discount'],
            'total_discount' => round($couponDiscount + $promo['discount'], 2),
            'free_shipping' => $promo['free_shipping'],
            'promotion_label' => $promo['label'],
            'coupon_code' => $this->coupons->appliedCode(),
        ];
    }
}
