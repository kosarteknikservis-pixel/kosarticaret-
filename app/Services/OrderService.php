<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrtakPanel\OrtakPanelOrderSyncService;
use App\Services\Payment\PaymentManager;
use App\Services\Telegram\OrderTelegramNotifier;
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
        private OrderTelegramNotifier $telegram,
        private OrtakPanelOrderSyncService $ortakPanel,
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
            $this->cart->totalDesi(),
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
                    'havale_indirim' => $totals['havale_discount'],
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
                $this->telegram->queue($order);
                $this->ortakPanel->queue($order);
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
            'payment_failed_at' => null,
        ]);
        $order = $order->fresh('items');
        $this->mail->sendOrderConfirmation($order);
        $this->telegram->queue($order);
        $this->ortakPanel->queue($order);
    }

    public function markPaymentFailed(Order $order, string $source = 'paytr_callback', ?string $reason = null): void
    {
        if ($order->payment_status === 'basarili') {
            return;
        }

        $alreadyFailed = $order->payment_status === 'basarisiz';

        $order->update([
            'payment_status' => 'basarisiz',
            'status' => 'odeme_bekliyor',
            'payment_failed_at' => $order->payment_failed_at ?? now(),
        ]);

        if ($alreadyFailed) {
            return;
        }

        $message = match ($source) {
            'paytr_return' => 'Müşteri PayTR hata sayfasına yönlendirildi.',
            default => 'PayTR ödeme bildirimi başarısız.',
        };

        $order->logs()->create([
            'type' => 'payment_failed',
            'message' => $message,
            'new_values' => array_filter([
                'source' => $source,
                'reason' => $reason,
            ]),
        ]);

        $this->releaseReservedStock($order);
    }

    /**
     * Başarısız ödeme sonrası stoku serbest bırakır (yalnızca bir kez).
     * Yeniden ödeme denemesinden önce {@see reserveStockForPaymentRetry} çağrılmalıdır.
     */
    public function releaseReservedStock(Order $order): void
    {
        $order->loadMissing('items.product');

        $alreadyReleased = $order->logs()
            ->where('type', 'stock_released_after_payment_failed')
            ->exists();

        if ($alreadyReleased) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', max(0, (int) $item->quantity));
            }
        }

        $order->logs()->create([
            'type' => 'stock_released_after_payment_failed',
            'message' => 'Başarısız ödeme sonrası stok iade edildi.',
        ]);
    }

    /**
     * Ödeme yeniden denemeden önce stoku tekrar rezerve eder.
     *
     * @throws \RuntimeException stok yetersizse
     */
    public function reserveStockForPaymentRetry(Order $order): void
    {
        if ($order->payment_status === 'basarili') {
            return;
        }

        $order->loadMissing('items.product');

        $latestStockLog = $order->logs()
            ->whereIn('type', [
                'stock_released_after_payment_failed',
                'stock_reserved_for_payment_retry',
            ])
            ->latest('id')
            ->first();

        if (! $latestStockLog || $latestStockLog->type !== 'stock_released_after_payment_failed') {
            return;
        }

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }
            $qty = max(0, (int) $item->quantity);
            if ($product->stock < $qty) {
                throw new \RuntimeException($product->name.' için yeterli stok kalmadı.');
            }
            $product->decrement('stock', $qty);
        }

        $order->logs()->create([
            'type' => 'stock_reserved_for_payment_retry',
            'message' => 'Ödeme yeniden denemesi için stok tekrar rezerve edildi.',
        ]);
    }

    /**
     * Gateway üzerinden iade + sipariş/ödeme durumunu günceller.
     *
     * @return array{ok: bool, message: string}
     */
    public function refundPayment(Order $order, ?float $amount = null): array
    {
        if ($order->payment_method !== 'kredi_karti') {
            return ['ok' => false, 'message' => 'Kart dışı ödemelerde gateway iadesi yok; manuel işlem gerekir.'];
        }

        if ($order->payment_status === 'iade') {
            return ['ok' => false, 'message' => 'Bu sipariş zaten iade edilmiş.'];
        }

        if ($order->payment_status !== 'basarili') {
            return ['ok' => false, 'message' => 'Yalnızca başarılı kart ödemeleri iade edilebilir.'];
        }

        $result = $this->payments->iade($order, $amount);
        if (! ($result['basarili'] ?? false)) {
            $order->logs()->create([
                'type' => 'payment_refund_failed',
                'message' => $result['mesaj'] ?? 'İade başarısız',
                'new_values' => array_filter([
                    'demo' => $result['demo'] ?? null,
                ]),
            ]);

            return ['ok' => false, 'message' => $result['mesaj'] ?? 'İade başarısız'];
        }

        $order->loadMissing('items.product');
        $address = $order->shipping_address ?? [];
        $alreadyRestored = (bool) ($address['stock_restored'] ?? false);

        if (! $alreadyRestored) {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', max(0, (int) $item->quantity));
                }
            }
            $address['stock_restored'] = true;
        }

        $order->update([
            'payment_status' => 'iade',
            'status' => 'iptal',
            'shipping_address' => $address,
        ]);

        $order->logs()->create([
            'type' => 'payment_refunded',
            'message' => ($result['demo'] ?? false)
                ? 'Demo iade kaydedildi.'
                : 'Ödeme iade edildi.',
            'new_values' => array_filter([
                'referans' => $result['referans'] ?? null,
                'amount' => $amount ?? (float) $order->total,
                'demo' => $result['demo'] ?? false,
            ]),
        ]);

        return ['ok' => true, 'message' => $result['demo'] ?? false
            ? 'Demo iade tamamlandı; sipariş iptal ve stok güncellendi.'
            : 'İade tamamlandı; sipariş iptal ve stok güncellendi.'];
    }
}
