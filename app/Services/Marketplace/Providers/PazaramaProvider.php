<?php

namespace App\Services\Marketplace\Providers;

use App\Services\Marketplace\AbstractMarketplaceProvider;

class PazaramaProvider extends AbstractMarketplaceProvider
{
    protected static function channelKey(): string
    {
        return 'pazarama';
    }

    public function credentialFields(): array
    {
        return [
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'seller_id' => 'Satıcı ID',
        ];
    }
}
