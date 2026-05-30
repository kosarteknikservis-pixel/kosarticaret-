<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'percent', 'fixed_amount', 'min_amount', 'active', 'expires_at', 'usage_limit',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }
}
