<?php

namespace App\Services\Telegram;

use App\Jobs\SendOrderTelegramNotificationJob;
use App\Models\Order;
use App\Models\OrderLog;
use App\Support\TelegramConfig;

class OrderTelegramNotifier
{
    public function __construct(
        private TelegramBotService $bot,
        private OrderTelegramMessageBuilder $messages,
    ) {}

    public function queue(Order|int $order): void
    {
        if (! TelegramConfig::isEnabled() || ! TelegramConfig::isConfigured()) {
            return;
        }

        $orderId = $order instanceof Order ? $order->id : $order;

        // Paylaşımlı hosting'de queue worker olmadığı için anında gönder.
        SendOrderTelegramNotificationJob::dispatchSync($orderId);
    }

    public function sendNow(Order $order): array
    {
        if (! TelegramConfig::isEnabled()) {
            return ['ok' => false, 'error' => 'Telegram bildirimi kapalı.'];
        }

        if (! TelegramConfig::isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram bot token veya chat ID eksik.'];
        }

        $order = $order->fresh('items');

        if ($order->telegram_notified_at) {
            return ['ok' => true, 'error' => null, 'skipped' => true];
        }

        $result = $this->bot->sendMessage($this->messages->build($order));

        if ($result['ok']) {
            $order->update(['telegram_notified_at' => now()]);
            OrderLog::query()->create([
                'order_id' => $order->id,
                'type' => 'telegram_sent',
                'message' => 'Telegram sipariş bildirimi gönderildi.',
                'new_values' => array_filter([
                    'message_id' => $result['message_id'] ?? null,
                ]),
            ]);

            return ['ok' => true, 'error' => null];
        }

        OrderLog::query()->create([
            'order_id' => $order->id,
            'type' => 'telegram_failed',
            'message' => 'Telegram bildirimi gönderilemedi: '.($result['error'] ?? 'Bilinmeyen hata'),
        ]);

        return ['ok' => false, 'error' => $result['error'] ?? 'Telegram bildirimi gönderilemedi.'];
    }

    /** @return array{ok: bool, error?: string} */
    public function sendTestMessage(): array
    {
        $siteUrl = rtrim((string) config('kosar.url', config('app.url')), '/');

        return $this->bot->sendMessage(
            "Yeni Sipariş ✅\n#TEST - ".now()->timezone(config('kosar.report_timezone', 'Europe/Istanbul'))->format('Y-m-d H:i:s')."\n\nBu bir test bildirimidir.\n\n🔗 Site: {$siteUrl}",
        );
    }
}
