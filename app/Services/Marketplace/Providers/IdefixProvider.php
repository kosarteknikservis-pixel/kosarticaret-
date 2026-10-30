<?php

namespace App\Services\Marketplace\Providers;

use App\Contracts\MarketplaceConnectionResult;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\AbstractMarketplaceProvider;
use App\Services\Marketplace\Idefix\IdefixApiClient;
use App\Services\Marketplace\Idefix\IdefixApiException;
use Throwable;

class IdefixProvider extends AbstractMarketplaceProvider
{
    public function __construct(
        private IdefixApiClient $apiClient,
    ) {}

    protected static function channelKey(): string
    {
        return 'idefix';
    }

    public function credentialFields(): array
    {
        return [
            'seller_id' => 'Satıcı ID (vendorId)',
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
        ];
    }

    protected function ping(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        try {
            $result = $this->apiClient->forChannel($channel)->ping();
            $brandId = data_get($result, 'id');
            $brandTitle = data_get($result, 'title');
            if ($brandId) {
                return MarketplaceConnectionResult::ok(
                    'Idefix API bağlantısı başarılı (marka: '.(string) $brandTitle.' #'.$brandId.').'
                );
            }

            $roots = (int) ($result['roots'] ?? 0);

            return MarketplaceConnectionResult::ok(
                'Idefix API bağlantısı başarılı (kategori kök: '.$roots.').'
            );
        } catch (IdefixApiException $e) {
            return MarketplaceConnectionResult::fail($e->getMessage());
        } catch (Throwable $e) {
            return MarketplaceConnectionResult::fail('Bağlantı testi başarısız: '.$e->getMessage());
        }
    }
}
