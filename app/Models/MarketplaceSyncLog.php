<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncLog extends Model
{
    protected $fillable = [
        'channel_key',
        'product_id',
        'order_id',
        'action',
        'status',
        'message',
        'context',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_key', 'key');
    }
}
