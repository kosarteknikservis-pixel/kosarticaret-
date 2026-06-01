<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private CheckoutCalculator $calculator,
        private CouponService $coupons,
        private CartPricingService $pricing,
        private PaymentManager $payments,
        private OrderMailService $mail,
        private StoreConfig $store,
    ) {}

    /**
     * @param  array<string, mixed>  $teslimat
     * @return array{order: Order, payment_url: ?string}
     */
    public function create(
        array $teslimat,
        string $shippingMethod,
        string $paymentMethod,
    ): array {
        $stockErrors = $this->cart->stockErrors();
        if ($stockErrors) {
            throw new \RuntimeException($stockErrors[0]);
        }

        $pricing = $this->pricing->breakdown();
        $totals = $this->calculator->totals(
            $pricing['subtotal'],
            $pricing['total_discount'],
            $shippingMethod,
            $paymentMethod,
            $pricing['free_shipping'],
        );
        $couponCode = $pricing['coupon_code'];

        $orderNumber = $this->calculator->orderNumber();
        $creditCard = $paymentMethod === 'kredi_karti';
        $shippingMethodData = collect($this->store->shippingMethods(true))
            ->firstWhere('id', $shippingMethod);

        return DB::transaction(function () use (
            $teslimat,
            $shippingMethod,
            $shippingMethodData,
            $paymentMethod,
            $totals,
            $pricing,
            $couponCode,
            $orderNumber,
            $creditCard,
        ) {
            $order = Order::query()->create([
                'user_id' => auth()->id(),
                'order_number' => $orderNumber,
                'email' => $teslimat['eposta'],
                'status' => $creditCard ? 'odeme_bekliyor' : 'hazirlaniyor',
                'payment_status' => $creditCard ? 'bekliyor' : 'basarili',
                'payment_method' => $paymentMethod,
                'customer_name' => trim(($teslimat['ad'] ?? '').' '.($teslimat['soyad'] ?? '')),
                'phone' => $teslimat['telefon'] ?? null,
                'shipping_address' => [
                    'teslimat' => $teslimat,
                    'kargo_yontemi' => $shippingMethod,
                    'kargo_firma' => $shippingMethodData,
                    'kdv' => $totals['vat'],
                    'kapida_ucret' => $totals['cod_fee'],
                    'promotion_label' => $pricing['promotion_label'],
                    'coupon_discount' => $pricing['coupon_discount'],
                    'promotion_discount' => $pricing['promotion_discount'],
                ],
                'subtotal' => $totals['subtotal'],
                'shipping_cost' => $totals['shipping'],
                'discount' => $pricing['total_discount'],
                'total' => $totals['total'],
                'coupon_code' => $couponCode,
            ]);

            foreach ($this->cart->lines() as $line) {
                $p = $line['product'];
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'sku' => $p->sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $p->price,
                    'line_total' => $line['line_total'],
                ]);

                $p->decrement('stock', $line['quantity']);
            }

            $order = $order->fresh('items');
            $paymentUrl = null;

            if ($creditCard) {
                $odeme = $this->payments->baslat($order);
                if (! $odeme['basarili'] || ! $odeme['odeme_url']) {
                    throw new \RuntimeException($odeme['mesaj'] ?? 'Ödeme başlatılamadı');
                }
                $paymentUrl = $odeme['odeme_url'];
            } else {
                $this->mail->sendOrderConfirmation($order);
            }

            $this->cart->clear();
            $this->coupons->remove();

            return ['order' => $order, 'payment_url' => $paymentUrl];
        });
    }

    public function confirmPayment(Order $order): void
    {
        if ($order->payment_status === 'basarili') {
            return;
        }

        $order->update([
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
        ]);
        $this->mail->sendOrderConfirmation($order->fresh('items'));
    }
}
