<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use App\Services\Marketplace\Trendyol\TrendyolApiException;
use App\Services\Marketplace\Trendyol\TrendyolListingPayloadBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductListingService
{
    public function __construct(
        private ProductReadinessChecker $readiness,
        private TrendyolApiClient $apiClient,
        private TrendyolListingPayloadBuilder $payloadBuilder,
        private MarketplaceChannelPricing $pricing,
        private MarketplaceSyncLogger $logger,
    ) {}

    /**
     * @return array{ok: bool, reasons: list<string>}
     */
    public function validateForPublish(Product $product, MarketplaceChannel $channel): array
    {
        $reasons = [];

        if (! $channel->is_active) {
            $reasons[] = 'Kanal pasif.';
        }

        if (! $channel->isConfigured()) {
            $reasons[] = 'Kanal API bilgileri eksik.';
        }

        $evaluation = $this->readiness->evaluate($product);

        if (! $evaluation['ready']) {
            $reasons = array_merge($reasons, $this->readiness->missingLabels($product));
        }

        if (! $this->readiness->categoryMapped($product, $channel->key)) {
            $reasons[] = 'Kategori eşleştirmesi yok.';
        }

        if (! $this->readiness->brandMapped($product, $channel->key)) {
            $reasons[] = 'Marka eşleştirmesi yok.';
        }

        return [
            'ok' => $reasons === [],
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    /**
     * @return array{success: bool, listing: MarketplaceListing, message: string}
     */
    public function publish(int $productId, string $channelKey = 'trendyol'): array
    {
        if ($channelKey !== 'trendyol') {
            throw new InvalidArgumentException('Faz 3 yalnızca Trendyol kanalını destekler.');
        }

        $channel = MarketplaceChannel::query()->where('key', $channelKey)->firstOrFail();
        $product = Product::query()->with(['brand', 'categories', 'images'])->findOrFail($productId);

        $validation = $this->validateForPublish($product, $channel);

        if (! $validation['ok']) {
            $message = implode(' ', $validation['reasons']);

            MarketplaceListing::query()->updateOrCreate(
                ['product_id' => $product->id, 'channel_key' => $channel->key],
                ['status' => 'error', 'last_error' => $message, 'external_sku' => $product->sku],
            );

            throw new InvalidArgumentException($message);
        }

        $listing = MarketplaceListing::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'channel_key' => $channel->key,
            ],
            [
                'status' => 'draft',
                'external_sku' => $product->sku,
            ],
        );

        $listing->fill([
            'channel_price' => $this->pricing->salePrice($product, $channel, $listing),
            'channel_stock_limit' => $this->pricing->stockQuantity($product, $channel, $listing),
            'last_error' => null,
        ])->save();

        $started = microtime(true);

        try {
            $client = $this->apiClient->forChannel($channel);

            if ($listing->status === 'published' && filled($listing->external_product_id)) {
                $payload = $this->payloadBuilder->buildInventoryPayload($product, $channel, $listing);
                $response = $client->updatePriceAndInventory($payload);
                $action = 'listing_inventory_update';
            } else {
                $payload = $this->payloadBuilder->buildCreatePayload($product, $channel, $listing);
                $response = $client->createProducts($payload);
                $action = 'listing_publish';
            }

            $batchId = data_get($response, 'batchRequestId') ?? data_get($response, 'batchId');

            $listing->update([
                'status' => 'pending',
                'external_product_id' => (string) ($batchId ?: $listing->external_product_id ?: $product->barcode),
                'external_sku' => $product->sku,
                'payload_snapshot' => $payload,
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            $this->logger->log(
                $action,
                'success',
                $channel->key,
                $product->id,
                null,
                'Trendyol gönderimi kuyruğa alındı.',
                ['response' => $response],
                (int) round((microtime(true) - $started) * 1000),
            );

            $channel->update(['last_sync_at' => now(), 'last_error' => null]);

            return [
                'success' => true,
                'listing' => $listing->fresh(),
                'message' => 'Ürün Trendyol\'a gönderildi. Onay süreci bekleniyor.',
            ];
        } catch (TrendyolApiException $e) {
            return $this->markListingError($listing, $channel, $product, $e->getMessage(), $e->response, $started, 'listing_publish');
        } catch (\Throwable $e) {
            return $this->markListingError($listing, $channel, $product, $e->getMessage(), null, $started, 'listing_publish');
        }
    }

    /**
     * @param  array<string, mixed>|null  $context
     * @return array{success: bool, listing: MarketplaceListing, message: string}
     */
    private function markListingError(
        MarketplaceListing $listing,
        MarketplaceChannel $channel,
        Product $product,
        string $message,
        ?array $context,
        float $started,
        string $action,
    ): array {
        DB::transaction(function () use ($listing, $channel, $message): void {
            $listing->update([
                'status' => 'error',
                'last_error' => $message,
            ]);

            $channel->update(['last_error' => $message]);
        });

        $this->logger->log(
            $action,
            'failed',
            $channel->key,
            $product->id,
            null,
            $message,
            $context,
            (int) round((microtime(true) - $started) * 1000),
        );

        return [
            'success' => false,
            'listing' => $listing->fresh(),
            'message' => $message,
        ];
    }
}
