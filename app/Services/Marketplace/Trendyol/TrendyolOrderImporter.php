<?php

namespace App\Services\Marketplace\Trendyol;

use App\Models\MarketplaceChannel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceSyncLogger;
use Illuminate\Support\Facades\DB;

class TrendyolOrderImporter
{
    public function __construct(
        private TrendyolApiClient $apiClient,
        private MarketplaceSyncLogger $logger,
    ) {}

    /**
     * @return array{imported: int, skipped: int, updated: int, errors: list<string>}
     */
    public function import(?MarketplaceChannel $channel = null): array
    {
        $channel ??= MarketplaceChannel::query()->where('key', 'trendyol')->firstOrFail();

        if (! $channel->is_active || ! $channel->isConfigured()) {
            throw new \RuntimeException('Trendyol kanalı aktif değil veya API bilgileri eksik.');
        }

        $client = $this->apiClient->forChannel($channel);
        $pageSize = (int) config('marketplace.trendyol.order_import_page_size', 50);
        $startDate = $this->resolveStartTimestamp($channel);
        $endDate = (int) (now()->getTimestamp() * 1000);

        $imported = 0;
        $skipped = 0;
        $updated = 0;
        $errors = [];
        $page = 0;

        do {
            $started = microtime(true);

            try {
                $response = $client->fetchOrders($page, $pageSize, $startDate, $endDate);
            } catch (TrendyolApiException $e) {
                $this->logger->log('order_import', 'failed', $channel->key, null, null, $e->getMessage(), $e->response);
                throw $e;
            }

            $rows = $response['content'] ?? [];

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                try {
                    $result = $this->importPackage($row);

                    if ($result === 'imported') {
                        $imported++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = (string) (data_get($row, 'orderNumber') ?? '?').': '.$e->getMessage();
                }
            }

            $totalPages = (int) ($response['totalPages'] ?? 0);
            $page++;
        } while ($page < $totalPages && $page < 20);

        $settings = $channel->settings ?? [];
        $settings['orders_last_sync_at'] = now()->toIso8601String();
        $channel->update([
            'settings' => $settings,
            'last_sync_at' => now(),
            'last_error' => $errors === [] ? null : implode('; ', array_slice($errors, 0, 3)),
        ]);

        $this->logger->log(
            'order_import',
            $errors === [] ? 'success' : 'failed',
            $channel->key,
            null,
            null,
            sprintf('%d yeni, %d güncellendi, %d atlandı.', $imported, $updated, $skipped),
            ['errors' => $errors],
            null,
        );

        return compact('imported', 'skipped', 'updated', 'errors');
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function importPackage(array $package): string
    {
        $orderNumber = (string) ($package['orderNumber'] ?? '');

        if ($orderNumber === '') {
            return 'skipped';
        }

        $existing = Order::query()
            ->where('sales_channel', 'trendyol')
            ->where('external_order_id', $orderNumber)
            ->first();

        if ($existing) {
            $this->syncExistingOrderStatus($existing, $package);

            return 'updated';
        }

        DB::transaction(function () use ($package, $orderNumber): void {
            $lines = collect($package['lines'] ?? [])->filter(fn ($line) => is_array($line));

            $subtotal = $lines->sum(fn (array $line) => (float) ($line['amount'] ?? $line['price'] ?? 0));
            $total = (float) ($package['totalPrice'] ?? $package['grossAmount'] ?? $subtotal);
            $customerName = trim(((string) ($package['customerFirstName'] ?? '')).' '.((string) ($package['customerLastName'] ?? '')));

            $order = Order::query()->create([
                'order_number' => $this->orderNumber($orderNumber),
                'email' => (string) ($package['customerEmail'] ?? 'trendyol@marketplace.local'),
                'status' => $this->mapStatus((string) ($package['shipmentPackageStatus'] ?? 'Created')),
                'payment_status' => 'basarili',
                'payment_method' => 'pazaryeri',
                'customer_name' => $customerName !== '' ? $customerName : 'Trendyol Müşteri',
                'phone' => data_get($package, 'shipmentAddress.phone') ?? data_get($package, 'invoiceAddress.phone'),
                'shipping_address' => [
                    'kaynak' => 'trendyol',
                    'shipment' => $package['shipmentAddress'] ?? null,
                    'invoice' => $package['invoiceAddress'] ?? null,
                    'cargo_provider' => $package['cargoProviderName'] ?? null,
                ],
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'discount' => max(0, $subtotal - $total),
                'total' => $total,
                'sales_channel' => 'trendyol',
                'external_order_id' => $orderNumber,
                'external_package_id' => (string) ($package['id'] ?? $package['packageNumber'] ?? null),
                'marketplace_commission' => (float) ($package['totalTyCommission'] ?? 0),
                'marketplace_payload' => $package,
            ]);

            foreach ($lines as $line) {
                $product = $this->resolveProduct($line);
                $quantity = max(1, (int) ($line['quantity'] ?? 1));
                $unitPrice = (float) ($line['price'] ?? $line['amount'] ?? 0);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_name' => (string) ($line['productName'] ?? 'Trendyol ürün'),
                    'sku' => (string) ($line['merchantSku'] ?? $line['sku'] ?? ''),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                ]);

                if ($product) {
                    $product->decrement('stock', min($quantity, max(0, (int) $product->stock)));
                }
            }
        });

        return 'imported';
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function syncExistingOrderStatus(Order $order, array $package): void
    {
        $status = $this->mapStatus((string) ($package['shipmentPackageStatus'] ?? ''));

        if ($status !== $order->status) {
            $order->update([
                'status' => $status,
                'marketplace_payload' => $package,
                'external_package_id' => (string) ($package['id'] ?? $package['packageNumber'] ?? $order->external_package_id),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function resolveProduct(array $line): ?Product
    {
        $barcode = trim((string) ($line['barcode'] ?? ''));

        if ($barcode !== '') {
            $byBarcode = Product::query()->where('barcode', $barcode)->first();

            if ($byBarcode) {
                return $byBarcode;
            }
        }

        $sku = trim((string) ($line['merchantSku'] ?? $line['sku'] ?? ''));

        if ($sku !== '') {
            return Product::query()->where('sku', $sku)->first();
        }

        return null;
    }

    private function mapStatus(string $trendyolStatus): string
    {
        return match ($trendyolStatus) {
            'Shipped', 'AtCollectionPoint' => 'kargoda',
            'Delivered' => 'teslim_edildi',
            'Cancelled', 'UnDelivered', 'Returned' => 'iptal',
            default => 'hazirlaniyor',
        };
    }

    private function orderNumber(string $externalOrderNumber): string
    {
        return 'TY-'.$externalOrderNumber;
    }

    private function resolveStartTimestamp(MarketplaceChannel $channel): ?int
    {
        $lastSync = $channel->setting('orders_last_sync_at');

        if ($lastSync) {
            return (int) (now()->parse($lastSync)->subMinutes(30)->getTimestamp() * 1000);
        }

        return (int) (now()->subDays(7)->getTimestamp() * 1000);
    }
}
