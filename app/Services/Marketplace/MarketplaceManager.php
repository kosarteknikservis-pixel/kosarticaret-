<?php

namespace App\Services\Marketplace;

use App\Contracts\MarketplaceProvider;
use App\Models\MarketplaceChannel;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MarketplaceManager
{
    /** @var array<string, MarketplaceProvider> */
    private array $providers = [];

    public function provider(string $key): MarketplaceProvider
    {
        if (isset($this->providers[$key])) {
            return $this->providers[$key];
        }

        $class = config('marketplace.channels.'.$key.'.provider');

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidArgumentException('Pazaryeri sağlayıcısı bulunamadı: '.$key);
        }

        return $this->providers[$key] = app($class);
    }

    /** @return Collection<int, MarketplaceChannel> */
    public function channels(): Collection
    {
        return MarketplaceChannel::query()->orderBy('sort_order')->get();
    }

    public function channel(string $key): MarketplaceChannel
    {
        return MarketplaceChannel::query()->where('key', $key)->firstOrFail();
    }

    /** @return Collection<int, MarketplaceChannel> */
    public function activeChannels(): Collection
    {
        return MarketplaceChannel::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
