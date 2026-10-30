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
        {--listing= : marketplace_listings.id (external_product_id kullanılır)}
        {--barcode= : Alternatif: ürün listesinde barkod ile ara}';

    protected $description = 'Idefix batch-result durumunu sorgular.';

    public function handle(IdefixApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'idefix')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('Idefix kanalı yapılandırılmamış.');

            return self::FAILURE;
        }

        $client = $apiClient->forChannel($channel);
        $batchId = trim((string) ($this->argument('batchRequestId') ?: ''));
        $listingId = (int) ($this->option('listing') ?: 0);
        $barcode = trim((string) ($this->option('barcode') ?: ''));

        if ($batchId === '' && $listingId > 0) {
            $listing = MarketplaceListing::query()->find($listingId);
            $batchId = trim((string) ($listing?->external_product_id ?: ''));
            if ($barcode === '' && is_array($listing?->payload_snapshot)) {
                $barcode = (string) data_get($listing->payload_snapshot, 'products.0.barcode', '');
            }
        }

        if ($batchId === '' && $barcode === '') {
            $this->error('batchRequestId, --listing veya --barcode gerekli.');

            return self::FAILURE;
        }

        if ($batchId !== '') {
            try {
                $result = $client->batchResult($batchId);
                $this->line('batchRequestId: '.$batchId);
                $this->line('status: '.(string) data_get($result, 'status', '—'));
                $this->dumpProducts(data_get($result, 'products') ?? data_get($result, 'items'));

                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->warn('batch-result: '.$e->getMessage());
                if ($barcode === '') {
                    $this->error($e->getMessage());

                    return self::FAILURE;
                }
            }
        }

        try {
            $listed = $client->listProducts(1, 20, $barcode !== '' ? $barcode : null);
        } catch (\Throwable $e) {
            $this->error('list: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Ürün listesi (barcode='.($barcode !== '' ? $barcode : '—').')');
        $this->dumpProducts(data_get($listed, 'products') ?? data_get($listed, 'items') ?? data_get($listed, 'items'));

        return self::SUCCESS;
    }

    private function dumpProducts(mixed $products): void
    {
        if (! is_array($products) || $products === []) {
            $this->line('(ürün yok)');

            return;
        }

        foreach ($products as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->newLine();
            $this->info('Ürün #'.((int) $i + 1));
            $this->line('  barcode: '.(string) ($row['barcode'] ?? ''));
            $this->line('  vendorStockCode: '.(string) ($row['vendorStockCode'] ?? ''));
            $this->line('  title: '.(string) ($row['title'] ?? $row['name'] ?? ''));
            $this->line('  status/poolState: '.(string) ($row['status'] ?? $row['poolState'] ?? ''));
            $reasons = $row['failureReasons'] ?? null;
            if (is_array($reasons) && $reasons !== []) {
                $this->warn('  failureReasons: '.json_encode($reasons, JSON_UNESCAPED_UNICODE));
            } elseif (is_string($reasons) && $reasons !== '') {
                $this->warn('  failureReasons: '.$reasons);
            }
        }
    }
}
