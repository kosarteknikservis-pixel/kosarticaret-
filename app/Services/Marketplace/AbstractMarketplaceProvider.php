<?php

namespace App\Services\Marketplace;

use App\Contracts\MarketplaceConnectionResult;
use App\Contracts\MarketplaceProvider;
use App\Models\MarketplaceChannel;
use InvalidArgumentException;

abstract class AbstractMarketplaceProvider implements MarketplaceProvider
{
    public function key(): string
    {
        return static::channelKey();
    }

    abstract protected static function channelKey(): string;

    public function label(): string
    {
        return (string) config('marketplace.channels.'.static::channelKey().'.label', static::channelKey());
    }

    public function type(): string
    {
        return (string) config('marketplace.channels.'.static::channelKey().'.type', 'marketplace');
    }

    public function testConnection(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        if ($channel->key !== static::channelKey()) {
            return MarketplaceConnectionResult::fail('Kanal eşleşmesi hatalı.');
        }

        if (! $channel->isConfigured()) {
            return MarketplaceConnectionResult::fail('API bilgileri henüz girilmedi.');
        }

        return $this->ping($channel);
    }

    protected function ping(MarketplaceChannel $channel): MarketplaceConnectionResult
    {
        return MarketplaceConnectionResult::ok('Kimlik bilgileri kayıtlı. Canlı API testi bir sonraki fazda eklenecek.');
    }

    protected function requireCredential(MarketplaceChannel $channel, string $field): string
    {
        $value = trim((string) data_get($channel->credentials, $field, ''));

        if ($value === '') {
            throw new InvalidArgumentException($field.' alanı zorunludur.');
        }

        return $value;
    }
}
