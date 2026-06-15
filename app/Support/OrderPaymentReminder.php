<?php

namespace App\Support;

use App\Models\Order;

final class OrderPaymentReminder
{
    /** @return array{label: string, tone: string, detail: ?string} */
    public static function status(Order $order): array
    {
        if ($order->payment_reminder_sent_at) {
            return [
                'label' => 'Gönderildi',
                'tone' => 'success',
                'detail' => $order->payment_reminder_sent_at->format('d.m.Y H:i'),
            ];
        }

        $failure = $order->lastPaymentReminderFailureLog();
        if ($failure) {
            $error = is_array($failure->new_values)
                ? ($failure->new_values['error'] ?? null)
                : null;

            return [
                'label' => 'Gönderilemedi',
                'tone' => 'danger',
                'detail' => trim(($failure->created_at->format('d.m.Y H:i')).($error ? ' · '.$error : '')),
            ];
        }

        $scheduled = $order->scheduledPaymentReminderAt();
        if ($scheduled && $scheduled->isFuture()) {
            return [
                'label' => 'Otomatik planlandı',
                'tone' => 'warning',
                'detail' => $scheduled->format('d.m.Y H:i'),
            ];
        }

        if ($order->isEligibleForAutoPaymentReminder()) {
            return [
                'label' => 'Otomatik sırada',
                'tone' => 'warning',
                'detail' => 'Bir sonraki saatlik kontrolde gönderilir',
            ];
        }

        if ($order->isPendingPayment()) {
            return [
                'label' => 'Henüz gönderilmedi',
                'tone' => 'muted',
                'detail' => null,
            ];
        }

        return [
            'label' => '—',
            'tone' => 'muted',
            'detail' => null,
        ];
    }

    public static function badgeClasses(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-50 text-emerald-700',
            'danger' => 'bg-red-50 text-red-700',
            'warning' => 'bg-amber-50 text-amber-800',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public static function logSuccess(Order $order, string $source, ?int $userId = null): void
    {
        $order->update(['payment_reminder_sent_at' => now()]);
        $order->logs()->create([
            'user_id' => $userId,
            'type' => 'payment_reminder',
            'message' => $source === 'manual'
                ? 'Ödeme hatırlatma e-postası panelden gönderildi.'
                : 'Ödeme hatırlatma e-postası otomatik gönderildi.',
            'new_values' => [
                'recipient' => $order->email,
                'source' => $source,
            ],
        ]);
    }

    public static function logFailure(Order $order, string $source, string $error, ?int $userId = null): void
    {
        $order->logs()->create([
            'user_id' => $userId,
            'type' => 'payment_reminder_failed',
            'message' => $source === 'manual'
                ? 'Ödeme hatırlatma e-postası panelden gönderilemedi.'
                : 'Ödeme hatırlatma e-postası otomatik gönderilemedi.',
            'new_values' => [
                'recipient' => $order->email,
                'source' => $source,
                'error' => $error,
            ],
        ]);
    }
}
