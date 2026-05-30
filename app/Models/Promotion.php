<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_FREE_SHIPPING = 'free_shipping';

    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    protected $fillable = [
        'name', 'type', 'value', 'buy_qty', 'free_qty',
        'min_cart', 'auto_apply', 'active', 'starts_at', 'ends_at', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_cart' => 'decimal:2',
            'auto_apply' => 'boolean',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority');
    }
}
