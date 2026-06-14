<?php

namespace App\Services\Marketplace\Providers;

use App\Services\Marketplace\AbstractMarketplaceProvider;

class N11Provider extends AbstractMarketplaceProvider
{
    protected static function channelKey(): string
    {
        return 'n11';
    }

    public function credentialFields(): array
    {
        return [
            'app_key' => 'App Key',
            'app_secret' => 'App Secret',
        ];
    }
}
