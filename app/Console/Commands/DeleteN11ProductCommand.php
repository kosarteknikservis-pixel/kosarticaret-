<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\N11\N11ApiClient;
use Illuminate\Console\Command;
use Throwable;

class DeleteN11ProductCommand extends Command
{
    protected $signature = 'marketplace:n11-delete-product {stockCode : N11 stok kodu}';

    protected $description = 'N11 ürününü stockCode ile siler.';

    public function handle(N11ApiClient $apiClient): int
    {
        $stockCode = strtoupper(trim((string) $this->argument('stockCode')));
        $channel = MarketplaceChannel::query()->where('key', 'n11')->first();
        if ($channel === null || ! $channel->isConfigured()) {
            $this->error('N11 kanalı yapılandırılmamış.');

            return self::FAILURE;
        }

        $integrator = (string) $channel->setting(
            'integrator',
            config('marketplace.n11.integrator', 'KosarTicaret')
        );

        try {
            $response = $apiClient->forChannel($channel)->deleteProducts([$stockCode], $integrator);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('N11 silme kuyruğa alındı: '.json_encode($response, JSON_UNESCAPED_UNICODE));

        $product = Product::query()->where('sku', $stockCode)->orWhere('sku', strtolower($stockCode))->first();
        if ($product !== null) {
            MarketplaceListing::query()
                ->where('product_id', $product->id)
                ->where('channel_key', 'n11')
                ->update([
                    'status' => 'delisted',
                    'last_error' => null,
                    'external_product_id' => null,
                ]);
        }

        return self::SUCCESS;
    }
}
