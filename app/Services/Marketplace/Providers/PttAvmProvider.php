<?php

namespace App\Services\Marketplace\Providers;

use App\Contracts\MarketplaceConnectionResult;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\AbstractMarketplaceProvider;
use App\Services\Marketplace\PttAvm\PttAvmApiClient;
use App\Services\Marketplace\PttAvm\PttAvmApiException;
use Throwable;

class PttAvmProvider extends AbstractMarketplaceProvider
{
    public function __construct(
        private PttAvmApiClient $apiClient,
    ) {}

    protected static function channelKey(): string
    {
        return 'pttavm';
    }

    public function credentialFields(): array
    {
        return [
            'api_key' => 'API Key',
            'access_token' => 'Access Token',
        ];
    }

    protected function ping(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        try {
            $result = $this->apiClient->forChannel($channel)->ping();
            $mains = $result['main_category'] ?? $result['items'] ?? [];
            $count = is_array($mains) ? count($mains) : 0;

            return MarketplaceConnectionResult::ok(
                'PTT AVM API bağlantısı başarılı (ana kategori: '.$count.').'
            );
        } catch (PttAvmApiException $e) {
            return MarketplaceConnectionResult::fail($e->getMessage());
        } catch (Throwable $e) {
            return MarketplaceConnectionResult::fail('Bağlantı testi başarısız: '.$e->getMessage());
        }
    }
}
