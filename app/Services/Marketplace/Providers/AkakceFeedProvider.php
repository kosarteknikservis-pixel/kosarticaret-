<?php

namespace App\Services\Marketplace\Providers;

use App\Services\Marketplace\AbstractMarketplaceProvider;

class AkakceFeedProvider extends AbstractMarketplaceProvider
{
    protected static function channelKey(): string
    {
        return 'akakce';
    }

    public function credentialFields(): array
    {
        return [
            'merchant_code' => 'Mağaza kodu',
            'feed_token' => 'Feed doğrulama anahtarı (opsiyonel)',
        ];
    }
}
