<?php

namespace App\Services\Shipping;

use App\Contracts\CarrierProvider;
use App\Support\CarrierConfig;

class CarrierManager
{
    public function provider(?string $carrier = null): CarrierProvider
    {
        $carrier ??= CarrierConfig::defaultCarrier();

        return match ($carrier) {
            'dhl' => app(DhlEcommerceProvider::class),
            default => app(DhlEcommerceProvider::class),
        };
    }

    public function activeProvider(): CarrierProvider
    {
        return $this->provider(CarrierConfig::defaultCarrier());
    }
}
