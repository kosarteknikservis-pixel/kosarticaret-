<?php

namespace App\Contracts;

use App\Models\MarketplaceChannel;

interface MarketplaceProvider
{
    public function key(): string;

    public function label(): string;

    public function type(): string;

    /** @return array<string, string> Credential field key => label */
    public function credentialFields(): array;

    public function testConnection(MarketplaceChannel $channel): MarketplaceConnectionResult;
}
