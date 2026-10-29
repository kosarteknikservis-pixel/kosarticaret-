<?php

namespace App\Services\Marketplace\Providers;

use App\Contracts\MarketplaceConnectionResult;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\AbstractMarketplaceProvider;
use App\Services\Marketplace\N11\N11ApiClient;
use App\Services\Marketplace\N11\N11ApiException;
use Throwable;

class N11Provider extends AbstractMarketplaceProvider
{
    public function __construct(
        private N11ApiClient $apiClient,
    ) {}

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

    protected function ping(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        try {
            $tree = $this->apiClient->forChannel($channel)->ping();
            $count = is_array($tree['categories'] ?? null) ? count($tree['categories']) : 0;

            return MarketplaceConnectionResult::ok('N11 API bağlantısı başarılı (kök kategori: '.$count.').');
        } catch (N11ApiException $e) {
            return MarketplaceConnectionResult::fail($e->getMessage());
        } catch (Throwable $e) {
            return MarketplaceConnectionResult::fail('Bağlantı testi başarısız: '.$e->getMessage());
        }
    }
}
