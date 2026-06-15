<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderMailService;
use App\Support\OrderPaymentReminder;
use Illuminate\Console\Command;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'orders:send-payment-reminders {--dry-run : Gönderim yapmadan aday siparişleri listele}';

    protected $description = 'Ödeme bekleyen kredi kartı siparişleri için hatırlatma e-postası gönderir';

    public function handle(OrderMailService $mail): int
    {
        if (! config('kosar.payment_reminder.enabled', true)) {
            $this->warn('Ödeme hatırlatması devre dışı (KOSAR_PAYMENT_REMINDER_ENABLED).');

            return self::SUCCESS;
        }

        $delayHours = max(1, (int) config('kosar.payment_reminder.delay_hours', 2));
        $maxAgeDays = max(1, (int) config('kosar.payment_reminder.max_age_days', 7));
        $cutoff = now()->subHours($delayHours);
        $minCreated = now()->subDays($maxAgeDays);

        $query = Order::query()
            ->pendingPayment()
            ->websiteChannel()
            ->whereNull('payment_reminder_sent_at')
            ->where('created_at', '<=', $cutoff)
            ->where('created_at', '>=', $minCreated)
            ->with('items')
            ->orderBy('id');

        $candidates = (clone $query)->count();

        if ($candidates === 0) {
            $this->info('Hatırlatma gönderilecek sipariş yok.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $query->get()->each(fn (Order $order) => $this->line("- {$order->order_number} · {$order->email} · {$order->total} ₺"));

            $this->info("Toplam aday: {$candidates}");

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        $query->chunkById(50, function ($orders) use ($mail, &$sent, &$failed): void {
            foreach ($orders as $order) {
                $result = $mail->sendPaymentReminder($order);

                if ($result['ok']) {
                    OrderPaymentReminder::logSuccess($order, 'automatic');
                    $sent++;

                    continue;
                }

                OrderPaymentReminder::logFailure($order, 'automatic', $result['error'] ?? 'Bilinmeyen hata');
                $failed++;
            }
        });

        $this->info("{$sent} hatırlatma gönderildi, {$failed} başarısız (aday: {$candidates}).");

        return self::SUCCESS;
    }
}
