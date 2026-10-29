<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\N11\N11ApiClient;
use Illuminate\Console\Command;
use Throwable;

class InspectN11TaskCommand extends Command
{
    protected $signature = 'marketplace:n11-inspect-task {taskId? : N11 task id}';

    protected $description = 'N11 task sonucu ve listing payload özetini gösterir.';

    public function handle(N11ApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'n11')->first();
        if ($channel === null) {
            $this->error('n11 kanalı yok.');

            return self::FAILURE;
        }

        $this->info('=== N11 LISTINGS ===');
        $listings = MarketplaceListing::query()
            ->where('channel_key', 'n11')
            ->with(['product.brand'])
            ->orderByDesc('id')
            ->get();

        foreach ($listings as $row) {
            $product = $row->product;
            $this->line(sprintf(
                'LISTING #%d product=%s sku=%s status=%s ext=%s price=%s',
                $row->id,
                $row->product_id,
                (string) ($product?->sku ?: '-'),
                (string) $row->status,
                (string) ($row->external_product_id ?: '-'),
                (string) ($row->channel_price ?: '-')
            ));

            $sku = data_get($row->payload_snapshot, 'payload.skus.0');
            if (is_array($sku)) {
                $this->line('TITLE='.(string) ($sku['title'] ?? ''));
                $this->line('STOCKCODE='.(string) ($sku['stockCode'] ?? ''));
                $this->line('BARCODE='.(string) ($sku['barcode'] ?? ''));
                $this->line('CAT='.(string) ($sku['categoryId'] ?? ''));
                $this->line('QTY='.(string) ($sku['quantity'] ?? ''));
                $this->line('SALE='.(string) ($sku['salePrice'] ?? ''));
                $this->line('SHIP='.(string) ($sku['shipmentTemplate'] ?? ''));
                $this->line('IMG='.(string) data_get($sku, 'images.0.url', ''));
            }

            if ($product !== null) {
                $this->line('PRODUCT_NAME='.(string) $product->name);
                $this->line('PRODUCT_SLUG='.(string) $product->slug);
                $this->line('BRAND='.(string) ($product->brand?->name ?: '-'));
            }
            $this->newLine();
        }

        $taskId = (int) ($this->argument('taskId') ?: 0);
        if ($taskId <= 0 && $listings->isNotEmpty()) {
            $taskId = (int) ($listings->first()->external_product_id ?: 0);
        }

        if ($taskId <= 0) {
            $this->warn('taskId yok.');

            return self::SUCCESS;
        }

        $this->info('=== N11 TASK '.$taskId.' ===');
        try {
            $details = $apiClient->forChannel($channel)->taskDetails($taskId);
            $this->line(json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            $this->error('Task sorgu: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
