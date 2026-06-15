<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'email', 'status', 'payment_status', 'payment_method',
        'payment_failed_at', 'payment_reminder_sent_at',
        'customer_name', 'phone', 'shipping_address', 'shipping_tracking',
        'admin_note', 'subtotal', 'shipping_cost', 'discount', 'total', 'coupon_code',
        'analytics_visitor_id', 'order_source', 'order_medium', 'order_campaign', 'landing_url', 'referrer_url',
        'sales_channel', 'external_order_id', 'external_package_id', 'marketplace_commission', 'marketplace_payload',
        'parasut_sales_invoice_id', 'parasut_status', 'parasut_error', 'parasut_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'marketplace_commission' => 'decimal:2',
            'marketplace_payload' => 'array',
            'parasut_synced_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'payment_reminder_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class)->latest();
    }

    public function analyticsVisitor(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisitor::class, 'analytics_visitor_id');
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_method === 'kredi_karti'
            && $this->status === 'odeme_bekliyor'
            && in_array($this->payment_status, ['bekliyor', 'basarisiz'], true);
    }

    public function paymentPageUrl(): string
    {
        return route('checkout.payment', ['order' => $this->order_number]);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Order>  $query */
    public function scopePendingPayment($query)
    {
        return $query
            ->where('payment_method', 'kredi_karti')
            ->where('status', 'odeme_bekliyor')
            ->whereIn('payment_status', ['bekliyor', 'basarisiz']);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Order>  $query */
    public function scopeWebsiteChannel($query)
    {
        return $query->where(function ($inner) {
            $inner->whereNull('sales_channel')
                ->orWhere('sales_channel', '')
                ->orWhere('sales_channel', 'website');
        });
    }
}
