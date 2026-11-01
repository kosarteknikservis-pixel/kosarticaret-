<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Models\Order;

class MockPaymentProvider implements PaymentProvider
{
    public function baslat(Order $order): array
    {
        return [
            'basarili' => true,
            'odeme_url' => route('checkout.payment', ['order' => $order->order_number]).'?demo=1',
            'demo' => true,
            'mesaj' => 'Demo ödeme — gerçek tahsilat yapılmaz',
        ];
    }

    public function iade(Order $order, ?float $amount = null): array
    {
        return [
            'basarili' => true,
            'demo' => true,
            'mesaj' => 'Demo iade — gerçek ödeme sağlayıcıya istek gitmedi',
            'referans' => 'DEMO-REFUND-'.$order->order_number,
        ];
    }
}
