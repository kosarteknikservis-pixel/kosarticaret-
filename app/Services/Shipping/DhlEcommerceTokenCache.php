<?php

namespace App\Services\Shipping;

use App\Support\CarrierConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DhlEcommerceTokenCache
{
    private const TTL_MINUTES = 450; // JWT 8 saat; güvenli marj

    public function get(callable $fetch): string
    {
        $key = $this->cacheKey();

        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), function () use ($fetch): string {
            $result = $fetch();

            if (! ($result['ok'] ?? false) || blank($result['token'] ?? null)) {
                throw new \RuntimeException($result['message'] ?? 'DHL token alınamadı.');
            }

            return (string) $result['token'];
        });
    }

    public function forget(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        $settings = CarrierConfig::dhlSettings();
        $env = CarrierConfig::isSandbox('dhl') ? 'test' : 'prod';

        return 'dhl_mng_jwt_'.md5((string) ($settings['client_id'] ?? '')).'_'.$env;
    }
}
