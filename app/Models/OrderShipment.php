<?php

namespace App\Models;

use App\Support\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderShipment extends Model
{
    protected $fillable = [
        'order_id', 'package_number', 'carrier', 'status', 'external_id',
        'tracking_number', 'barcode', 'label_path', 'weight_kg', 'desi',
        'items', 'cod_amount', 'error_message', 'carrier_payload',
        'submitted_at', 'delivered_at', 'sms_sent_at', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'carrier_payload' => 'array',
            'weight_kg' => 'decimal:3',
            'desi' => 'decimal:2',
            'cod_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->latest('occurred_at');
    }

    public function statusLabel(): string
    {
        return ShipmentStatus::label($this->status);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canSubmit(): bool
    {
        return $this->isDraft() && blank($this->error_message);
    }

    /** @return list<array<string, mixed>> */
    public function itemLines(): array
    {
        return is_array($this->items) ? $this->items : [];
    }

    public function totalQuantity(): int
    {
        return (int) collect($this->itemLines())->sum('quantity');
    }
}
