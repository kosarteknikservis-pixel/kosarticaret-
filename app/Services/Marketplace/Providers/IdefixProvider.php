<?php

namespace App\Services\Marketplace\Providers;

use App\Services\Marketplace\AbstractMarketplaceProvider;

class IdefixProvider extends AbstractMarketplaceProvider
{
    protected static function channelKey(): string
    {
        return 'idefix';
    }

    public function credentialFields(): array
    {
        return [
            'seller_id' => 'Satıcı ID',
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
        ];
    }
}
