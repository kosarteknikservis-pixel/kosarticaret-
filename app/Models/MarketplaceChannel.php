<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceChannel extends Model
{
    protected $fillable = [
        'key',
        'name',
        'type',
        'is_active',
        'environment',
        'credentials',
        'settings',
        'last_sync_at',
        'last_error',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'channel_key', 'key');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class, 'channel_key', 'key');
    }

    public function isConfigured(): bool
    {
        $credentials = $this->credentials ?? [];

        return collect($credentials)->filter(fn ($value) => filled($value))->isNotEmpty();
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
