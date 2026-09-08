<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorOffer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'product_id',
        'competitor_name',
        'competitor_url',
        'competitor_title',
        'match_status',
        'last_price',
        'currency',
        'last_fetched_at',
        'last_error',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'last_price' => 'decimal:2',
            'last_fetched_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isApproved(): bool
    {
        return $this->match_status === self::STATUS_APPROVED;
    }

    public function statusLabel(): string
    {
        return match ($this->match_status) {
            self::STATUS_APPROVED => 'Onaylı eşleşme',
            self::STATUS_REJECTED => 'Reddedildi',
            default => 'Onay bekliyor',
        };
    }
}
