<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceSyncLog;

class MarketplaceSyncLogger
{
    /** @param  array<string, mixed>|null  $context */
    public function log(
        string $action,
        string $status,
        ?string $channelKey = null,
        ?int $productId = null,
        ?int $orderId = null,
        ?string $message = null,
        ?array $context = null,
        ?int $durationMs = null,
    ): MarketplaceSyncLog {
        return MarketplaceSyncLog::query()->create([
            'channel_key' => $channelKey,
            'product_id' => $productId,
            'order_id' => $orderId,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'context' => $context,
            'duration_ms' => $durationMs,
        ]);
    }
}
