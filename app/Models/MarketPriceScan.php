<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPriceScan extends Model
{
    public const STATUS_PENDING = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NO_RESULTS = 'no_results';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'product_id',
        'search_query',
        'status',
        'google_min_price',
        'google_median_price',
        'offer_count',
        'offers',
        'last_scanned_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'google_min_price' => 'decimal:2',
            'google_median_price' => 'decimal:2',
            'offers' => 'array',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->competitivePrice() !== null;
    }

    /**
     * Google bazen bayat düşük fiyat basar; min medyanın çok altındaysa medyanı kullan.
     */
    public function competitivePrice(): ?float
    {
        if ($this->google_min_price === null || (float) $this->google_min_price <= 0) {
            return null;
        }

        $min = round((float) $this->google_min_price, 2);
        $median = $this->google_median_price !== null ? round((float) $this->google_median_price, 2) : $min;

        if ($median > 0 && $min < ($median * 0.85)) {
            return $median;
        }

        return $min;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Onaylı piyasa',
            self::STATUS_REJECTED => 'Reddedildi',
            self::STATUS_NO_RESULTS => 'Sonuç yok',
            self::STATUS_ERROR => 'Hata',
            default => 'İnceleme bekliyor',
        };
    }
}
