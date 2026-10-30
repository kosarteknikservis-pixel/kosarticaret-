<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\Idefix\IdefixApiClient;
use Illuminate\Console\Command;

class InspectIdefixBatchCommand extends Command
{
    protected $signature = 'marketplace:idefix-batch
        {batchRequestId? : Idefix batchRequestId}
        {--listing= : marketplace_listings.id (external_product_id kullanılır)}';

    protected $description = 'Idefix batch-result durumunu sorgular.';

    public function handle(IdefixApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'idefix')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('Idefix kanalı yapılandırılmamış.');

            return self::FAILURE;
        }

        $batchId = trim((string) ($this->argument('batchRequestId') ?: ''));
        $listingId = (int) ($this->option('listing') ?: 0);

        if ($batchId === '' && $listingId > 0) {
            $listing = MarketplaceListing::query()->find($listingId);
            $batchId = trim((string) ($listing?->external_product_id ?: ''));
        }

        if ($batchId === '') {
            $this->error('batchRequestId veya --listing gerekli.');

            return self::FAILURE;
        }

        try {
            $result = $apiClient->forChannel($channel)->batchResult($batchId);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('batchRequestId: '.$batchId);
        $this->line('status: '.(string) data_get($result, 'status', '—'));

        $products = data_get($result, 'products');
        if (! is_array($products)) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '');

            return self::SUCCESS;
        }

        foreach ($products as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->newLine();
            $this->info('Ürün #'.((int) $i + 1));
            $this->line('  barcode: '.(string) ($row['barcode'] ?? ''));
            $this->line('  vendorStockCode: '.(string) ($row['vendorStockCode'] ?? ''));
            $this->line('  status/poolState: '.(string) ($row['status'] ?? $row['poolState'] ?? ''));
            $reasons = $row['failureReasons'] ?? null;
            if (is_array($reasons) && $reasons !== []) {
                $this->warn('  failureReasons: '.json_encode($reasons, JSON_UNESCAPED_UNICODE));
            } elseif (is_string($reasons) && $reasons !== '') {
                $this->warn('  failureReasons: '.$reasons);
            }
        }

        return self::SUCCESS;
    }
}
