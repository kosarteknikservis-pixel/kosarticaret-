<?php

namespace App\Services;

class CartPricingService
{
    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
        private PromotionService $promotions,
        private StoreConfig $store,
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
        $netSubtotal = max(0, round($subtotal - $couponDiscount - $promo['discount'], 2));
        $freeMin = $this->store->freeShippingMin();
        $freeShipping = $promo['free_shipping']
            || ($freeMin > 0 && $netSubtotal >= $freeMin);

        return [
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'promotion_discount' => $promo['discount'],
            'total_discount' => round($couponDiscount + $promo['discount'], 2),
            'free_shipping' => $freeShipping,
            'promotion_label' => $promo['label'],
            'coupon_code' => $this->coupons->appliedCode(),
        ];
    }
}
