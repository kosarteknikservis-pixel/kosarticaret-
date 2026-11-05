<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrtakPanel\OrtakPanelOrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncOrderToOrtakPanelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public int $orderId) {}

    public function handle(OrtakPanelOrderSyncService $sync): void
    {
        $order = Order::query()->with('items')->find($this->orderId);
        if (! $order) {
            return;
        }

        $result = $sync->sync($order);

        if (! ($result['ok'] ?? false) && ! ($result['skipped'] ?? false)) {
            throw new \RuntimeException($result['error'] ?? 'Ortak panel senkronu başarısız.');
        }
    }
}
