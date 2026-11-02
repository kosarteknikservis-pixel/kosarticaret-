<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'product_id',
        'channel_key',
        'external_product_id',
        'external_sku',
        'external_barcode',
        'status',
        'channel_price',
        'channel_stock_limit',
        'last_synced_at',
        'last_error',
        'payload_snapshot',
    ];

    /** Trendyol vb. kanal barkodu; yoksa ürün barkodu. */
    public function resolveBarcode(): string
    {
        $external = trim((string) ($this->external_barcode ?? ''));
        if ($external !== '') {
            return $external;
        }

        return trim((string) ($this->product?->barcode ?? ''));
    }

    protected function casts(): array
    {
        return [
            'channel_price' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'payload_snapshot' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_key', 'key');
    }

    public function statusLabel(): string
    {
        return config('marketplace.listing_statuses.'.$this->status, $this->status);
    }
}
