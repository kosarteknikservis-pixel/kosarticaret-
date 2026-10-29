<?php

namespace App\Services\Marketplace\Providers;

use App\Contracts\MarketplaceConnectionResult;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\AbstractMarketplaceProvider;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiClient;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiException;
use Throwable;

class HepsiburadaProvider extends AbstractMarketplaceProvider
{
    public function __construct(
        private HepsiburadaApiClient $apiClient,
    ) {}

    protected static function channelKey(): string
    {
        return 'hepsiburada';
    }

    public function credentialFields(): array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'username' => 'Kullanıcı adı (User-Agent)',
            'password' => 'Servis anahtarı / şifre',
        ];
    }

    protected function ping(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        try {
            $this->apiClient->forChannel($channel)->ping();

            return MarketplaceConnectionResult::ok('Hepsiburada API bağlantısı başarılı.');
        } catch (HepsiburadaApiException $e) {
            return MarketplaceConnectionResult::fail($e->getMessage());
        } catch (Throwable $e) {
            return MarketplaceConnectionResult::fail('Bağlantı testi başarısız: '.$e->getMessage());
        }
    }
}
