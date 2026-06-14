<?php

namespace App\Services\Marketplace\Providers;

use App\Contracts\MarketplaceConnectionResult;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\AbstractMarketplaceProvider;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use App\Services\Marketplace\Trendyol\TrendyolApiException;
use Throwable;

class TrendyolProvider extends AbstractMarketplaceProvider
{
    public function __construct(
        private TrendyolApiClient $apiClient,
    ) {}

    protected static function channelKey(): string
    {
        return 'trendyol';
    }

    public function credentialFields(): array
    {
        return [
            'supplier_id' => 'Satıcı ID (Supplier ID)',
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
        ];
    }

    protected function ping(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        try {
            $this->apiClient->forChannel($channel)->ping();

            return MarketplaceConnectionResult::ok('Trendyol API bağlantısı başarılı.');
        } catch (TrendyolApiException $e) {
            return MarketplaceConnectionResult::fail($e->getMessage());
        } catch (Throwable $e) {
            return MarketplaceConnectionResult::fail('Bağlantı testi başarısız: '.$e->getMessage());
        }
    }
}
