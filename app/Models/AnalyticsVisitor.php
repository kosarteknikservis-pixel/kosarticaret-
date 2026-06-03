<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsVisitor extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'ip_hash',
        'device_type',
        'user_agent',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer',
        'landing_url',
        'last_url',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'visitor_id');
    }

    public function carts(): HasMany
    {
        return $this->hasMany(AbandonedCart::class, 'visitor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'analytics_visitor_id');
    }
}
