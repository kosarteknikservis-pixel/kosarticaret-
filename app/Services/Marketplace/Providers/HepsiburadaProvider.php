<?php

namespace App\Services\Marketplace\Providers;

use App\Services\Marketplace\AbstractMarketplaceProvider;

class HepsiburadaProvider extends AbstractMarketplaceProvider
{
    protected static function channelKey(): string
    {
        return 'hepsiburada';
    }

    public function credentialFields(): array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'username' => 'Kullanıcı adı',
            'password' => 'Şifre',
        ];
    }
}
