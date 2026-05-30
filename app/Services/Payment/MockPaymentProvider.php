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
}
