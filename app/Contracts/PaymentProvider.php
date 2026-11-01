<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentProvider
{
    /**
     * @return array{basarili: bool, odeme_url: ?string, demo: bool, mesaj: ?string}
     */
    public function baslat(Order $order): array;

    /**
     * Kart ödemesini gateway üzerinden iade eder.
     *
     * @return array{basarili: bool, demo: bool, mesaj: ?string, referans: ?string}
     */
    public function iade(Order $order, ?float $amount = null): array;
}
