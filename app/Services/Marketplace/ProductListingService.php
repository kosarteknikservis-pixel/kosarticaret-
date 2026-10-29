<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiClient;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiException;
use App\Services\Marketplace\Hepsiburada\HepsiburadaListingPayloadBuilder;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use App\Services\Marketplace\Trendyol\TrendyolApiException;
use App\Services\Marketplace\Trendyol\TrendyolListingPayloadBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductListingService
{
    public function __construct(
        private ProductReadinessChecker $readiness,
        private TrendyolApiClient $trendyolApiClient,
        private TrendyolListingPayloadBuilder $trendyolPayloadBuilder,
        private HepsiburadaApiClient $hepsiburadaApiClient,
        private HepsiburadaListingPayloadBuilder $hepsiburadaPayloadBuilder,
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
    public function publish(int $productId, string $channelKey = 'trendyol', ?float $priceOverride = null): array
    {
        return match ($channelKey) {
            'trendyol' => $this->publishTrendyol($productId, $priceOverride),
            'hepsiburada' => $this->publishHepsiburada($productId, $priceOverride),
            default => throw new InvalidArgumentException('Bu kanal için ürün gönderimi henüz desteklenmiyor: '.$channelKey),
        };
    }

    /**
     * @return array{success: bool, listing: MarketplaceListing, message: string}
     */
    private function publishTrendyol(int $productId, ?float $priceOverride = null): array
    {
        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->firstOrFail();
        $product = Product::query()->with(['brand', 'categories', 'images'])->findOrFail($productId);
        $listing = $this->prepareListing($product, $channel, $priceOverride);
        $started = microtime(true);

        try {
            $client = $this->trendyolApiClient->forChannel($channel);

            if ($listing->status === 'published' && filled($listing->external_product_id) && ! str_contains((string) $listing->external_product_id, '-')) {
                $payload = $this->trendyolPayloadBuilder->buildInventoryPayload($product, $channel, $listing, $priceOverride);
                $response = $client->updatePriceAndInventory($payload);
                $action = 'listing_inventory_update';
            } else {
                if (in_array($listing->status, ['error', 'draft', 'pending', 'rejected'], true)) {
                    $listing->fill([
                        'status' => 'draft',
                        'last_error' => null,
                    ])->save();
                }

                $payload = $this->trendyolPayloadBuilder->buildCreatePayload($product, $channel, $listing, $priceOverride);
                $payload = $this->trendyolPayloadBuilder->enrichRequiredAttributes($payload, $client, [
                    433 => ['2 Yıl', '2 yıl', '2 sene', '24 Ay', '24 ay'],
                    1192 => ['Türkiye', 'TR', 'Turkiye'],
                    34 => $this->tipCandidatesForProduct($product),
                ]);

                $barcode = (string) ($product->barcode ?: '');
                $existing = $barcode !== '' ? $client->getProductsByBarcode($barcode) : [];
                $existingContent = data_get($existing, 'content.0') ?? data_get($existing, 'products.0');
                $useUnapprovedUpdate = is_array($existingContent) && $existingContent !== [];

                try {
                    if ($useUnapprovedUpdate) {
                        $response = $client->updateUnapprovedProducts($payload);
                        $action = 'listing_unapproved_update';
                    } else {
                        $response = $client->createProducts($payload);
                        $action = 'listing_publish';
                    }
                } catch (TrendyolApiException $createEx) {
                    // Tekrarlı create engeli: red/onay bekleyen kaydı unapproved update ile düzelt.
                    $msg = mb_strtolower($createEx->getMessage(), 'UTF-8');
                    if (! $useUnapprovedUpdate && (str_contains($msg, 'tekrarlı') || str_contains($msg, 'recurring'))) {
                        $response = $client->updateUnapprovedProducts($payload);
                        $action = 'listing_unapproved_update';
                    } else {
                        throw $createEx;
                    }
                }
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
     * @return array{success: bool, listing: MarketplaceListing, message: string}
     */
    private function publishHepsiburada(int $productId, ?float $priceOverride = null): array
    {
        $channel = MarketplaceChannel::query()->where('key', 'hepsiburada')->firstOrFail();
        $product = Product::query()->with(['brand', 'categories', 'images'])->findOrFail($productId);
        $listing = $this->prepareListing($product, $channel, $priceOverride);
        $started = microtime(true);

        try {
            $client = $this->hepsiburadaApiClient->forChannel($channel);

            if (in_array($listing->status, ['error', 'draft', 'pending', 'rejected'], true)) {
                $listing->fill([
                    'status' => 'draft',
                    'last_error' => null,
                ])->save();
            }

            $payload = $this->hepsiburadaPayloadBuilder->buildImportPayload(
                $product,
                $channel,
                $listing,
                $priceOverride,
                $client,
            );
            $response = $client->importProducts($payload);

            $trackingId = (string) (
                data_get($response, 'data.trackingId')
                ?? data_get($response, 'trackingId')
                ?? data_get($response, 'data.id')
                ?? ''
            );

            if ($trackingId === '') {
                throw new HepsiburadaApiException(
                    'Hepsiburada trackingId dönmedi.',
                    0,
                    is_array($response) ? $response : null,
                );
            }

            $listing->update([
                'status' => 'pending',
                'external_product_id' => $trackingId,
                'external_sku' => $product->sku,
                'payload_snapshot' => $payload,
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            $this->logger->log(
                'listing_publish',
                'success',
                $channel->key,
                $product->id,
                null,
                'Hepsiburada gönderimi kuyruğa alındı.',
                ['response' => $response, 'trackingId' => $trackingId],
                (int) round((microtime(true) - $started) * 1000),
            );

            $channel->update(['last_sync_at' => now(), 'last_error' => null]);

            return [
                'success' => true,
                'listing' => $listing->fresh(),
                'message' => 'Ürün Hepsiburada\'ya gönderildi. Onay süreci bekleniyor (tracking: '.$trackingId.').',
            ];
        } catch (HepsiburadaApiException $e) {
            return $this->markListingError($listing, $channel, $product, $e->getMessage(), $e->response, $started, 'listing_publish');
        } catch (\Throwable $e) {
            return $this->markListingError($listing, $channel, $product, $e->getMessage(), null, $started, 'listing_publish');
        }
    }

    private function prepareListing(Product $product, MarketplaceChannel $channel, ?float $priceOverride): MarketplaceListing
    {
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

        $sale = $this->pricing->salePrice($product, $channel, $listing, $priceOverride);
        $listing->fill([
            'channel_price' => $sale,
            'channel_stock_limit' => $this->pricing->stockQuantity($product, $channel, $listing),
            'last_error' => null,
        ])->save();

        return $listing->fresh();
    }

    /** @return list<string> */
    private function tipCandidatesForProduct(Product $product): array
    {
        $haystack = mb_strtolower(
            $product->name.' '.$product->categories->pluck('slug')->implode(' ').' '.$product->categories->pluck('name')->implode(' ')
        );

        if (str_contains($haystack, 'dalgic') || str_contains($haystack, 'dalgıç')) {
            return ['Dalgıç Pompa', 'Temiz Su Dalgıç', 'Dalgıç', 'Su Pompası', 'Drenaj Pompası'];
        }

        if (str_contains($haystack, 'hidrofor')) {
            return ['Hidrofor', 'Paket Hidrofor', 'Su Hidroforu', 'Ev Tipi Hidrofor'];
        }

        return ['Su Pompası', 'Dalgıç Pompa', 'Hidrofor', 'Paket Hidrofor'];
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
