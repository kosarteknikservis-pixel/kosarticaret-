<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentProvider
{
    /**
     * @return array{basarili: bool, odeme_url: ?string, demo: bool, mesaj: ?string}
     */
    public function baslat(Order $order): array;
}
