<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Telegram\OrderTelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [15, 60, 180];

    public function __construct(public int $orderId) {}

    public function handle(OrderTelegramNotifier $notifier): void
    {
        $order = Order::query()->with('items')->find($this->orderId);

        if (! $order) {
            return;
        }

        $result = $notifier->sendNow($order);

        if (! ($result['ok'] ?? false) && ! ($result['skipped'] ?? false)) {
            throw new \RuntimeException($result['error'] ?? 'Telegram bildirimi gönderilemedi.');
        }
    }
}
