<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    protected $fillable = [
        'visitor_id',
        'user_id',
        'converted_order_id',
        'email',
        'phone',
        'item_count',
        'subtotal',
        'items',
        'status',
        'started_checkout_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'items' => 'array',
            'started_checkout_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisitor::class, 'visitor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }
}
