<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceExternalCategory extends Model
{
    protected $fillable = [
        'channel_key',
        'external_id',
        'name',
        'path',
        'parent_external_id',
        'metadata',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_key', 'key');
    }

    public function displayLabel(): string
    {
        return $this->path ? $this->path.' → '.$this->name : $this->name;
    }
}
