<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Models\Order;
use App\Support\PaymentGatewayConfig;

class PaymentManager
{
    public function provider(): PaymentProvider
    {
        $active = PaymentGatewayConfig::activeProvider();

        if (! PaymentGatewayConfig::isConfigured($active)) {
            return new MockPaymentProvider;
        }

        return match ($active) {
            'iyzico' => new IyzicoPaymentProvider,
            'paytr' => new PaytrPaymentProvider,
            default => new MockPaymentProvider,
        };
    }

    public function baslat(Order $order): array
    {
        return $this->provider()->baslat($order);
    }
}
