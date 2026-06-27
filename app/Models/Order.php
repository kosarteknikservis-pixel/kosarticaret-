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
        'customer_name', 'phone', 'shipping_address', 'shipping_tracking', 'shipping_carrier',
        'shipment_sms_sent_at',
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
            'shipment_sms_sent_at' => 'datetime',
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

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class)->orderBy('package_number');
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

    /** @param  \Illuminate\Database\Eloquent\Builder<Order>  $query */
    public function scopeExcludePendingPayment($query)
    {
        return $query->where(function ($inner) {
            $inner->where('payment_method', '!=', 'kredi_karti')
                ->orWhere('status', '!=', 'odeme_bekliyor')
                ->orWhereNotIn('payment_status', ['bekliyor', 'basarisiz']);
        });
    }

    public function scheduledPaymentReminderAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->isPendingPayment() || $this->payment_reminder_sent_at) {
            return null;
        }

        return $this->created_at->copy()->addHours(max(1, (int) config('kosar.payment_reminder.delay_hours', 2)));
    }

    public function isEligibleForAutoPaymentReminder(): bool
    {
        if (! $this->isPendingPayment() || $this->payment_reminder_sent_at) {
            return false;
        }

        $delayHours = max(1, (int) config('kosar.payment_reminder.delay_hours', 2));
        $maxAgeDays = max(1, (int) config('kosar.payment_reminder.max_age_days', 7));

        return $this->created_at <= now()->subHours($delayHours)
            && $this->created_at >= now()->subDays($maxAgeDays);
    }

    public function lastPaymentReminderFailureLog(): ?OrderLog
    {
        if ($this->relationLoaded('logs')) {
            return $this->logs->firstWhere('type', 'payment_reminder_failed');
        }

        return $this->logs()->where('type', 'payment_reminder_failed')->latest('id')->first();
    }
}
