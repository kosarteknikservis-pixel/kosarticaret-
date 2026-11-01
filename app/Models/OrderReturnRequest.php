<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'note',
        'admin_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'beklemede';
    }

    public static function reasonLabels(): array
    {
        return [
            'vazgecme' => 'Almaktan vazgeçtim',
            'hasarli' => 'Ürün hasarlı / eksik geldi',
            'yanlis' => 'Yanlış ürün geldi',
            'uyumsuz' => 'Ürün ihtiyacıma uymadı',
            'diger' => 'Diğer',
        ];
    }
}
