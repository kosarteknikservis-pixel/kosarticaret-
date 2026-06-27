<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    protected $fillable = [
        'order_shipment_id', 'status', 'description', 'location', 'occurred_at', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'order_shipment_id');
    }
}
