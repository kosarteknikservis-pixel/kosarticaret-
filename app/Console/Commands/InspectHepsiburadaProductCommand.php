<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiClient;
use Illuminate\Console\Command;
use Throwable;

class InspectHepsiburadaProductCommand extends Command
{
    protected $signature = 'marketplace:hb-inspect
        {sku : Satıcı stok kodu (merchantSku)}
        {--tracking= : Opsiyonel trackingId}
        {--delete-pending : Aksiyon bekleyen kaydı sil}';

    protected $description = 'Hepsiburada ürün durumunu ve validasyon hatalarını gösterir.';

    public function handle(HepsiburadaApiClient $apiClient): int
    {
        $sku = strtoupper(trim((string) $this->argument('sku')));
        $channel = MarketplaceChannel::query()->where('key', 'hepsiburada')->first();
        if ($channel === null) {
            $this->error('Hepsiburada kanalı yok.');

            return self::FAILURE;
        }

        try {
            $client = $apiClient->forChannel($channel);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $tracking = trim((string) $this->option('tracking'));
        if ($tracking !== '') {
            $this->info('Tracking status: '.$tracking);
            $this->line(json_encode($client->getProductStatus($tracking), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $this->info('check-product-status: '.$sku);
        try {
            $checked = $client->checkProductStatus([$sku]);
            $this->line(json_encode($checked, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            $this->warn('check-product-status: '.$e->getMessage());
        }

        foreach (['MISSING_INFO', 'WAITING', 'REJECTED', 'CREATED', 'MATCHED'] as $status) {
            try {
                $rows = $client->getProductsByStatus($status, 0, 50);
                $data = $rows['data'] ?? [];
                if (! is_array($data) || $data === []) {
                    continue;
                }
                $hits = array_values(array_filter(
                    $data,
                    fn ($row) => is_array($row) && strtoupper((string) ($row['merchantSku'] ?? '')) === $sku
                ));
                if ($hits === []) {
                    continue;
                }
                $this->info('products-by-status '.$status.':');
                $this->line(json_encode($hits, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            } catch (Throwable $e) {
                $this->warn($status.': '.$e->getMessage());
            }
        }

        if ($this->option('delete-pending')) {
            $this->warn('Aksiyon bekleyen siliniyor: '.$sku);
            try {
                $deleted = $client->deletePendingProducts([$sku]);
                $this->line(json_encode($deleted, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            } catch (Throwable $e) {
                $this->error('Silme: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
