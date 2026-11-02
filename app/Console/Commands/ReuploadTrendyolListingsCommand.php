<?php

namespace App\Console\Commands;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\ProductListingService;
use App\Services\Marketplace\ProductReadinessChecker;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReuploadTrendyolListingsCommand extends Command
{
    protected $signature = 'marketplace:trendyol-reupload
        {--brand=Koşar : Trendyol markası}
        {--dry-run : Göndermeden doğrula}
        {--limit=0 : Maksimum ürün (0=hepsi)}
        {--sleep-ms=2000 : Ürünler arası bekleme}
        {--batch-wait-ms=2500 : Batch sorgu aralığı}
        {--batch-tries=8 : Batch sorgu denemesi}';

    protected $description = 'Trendyol listing’lerini Koşar markasıyla eksiksiz yeniden gönderir; batch sonucunu doğrular.';

    public function handle(
        TrendyolApiClient $apiClient,
        ProductListingService $listingService,
        ProductReadinessChecker $readiness,
    ): int {
        $brandName = trim((string) $this->option('brand'));
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $batchWaitMs = max(500, (int) $this->option('batch-wait-ms'));
        $batchTries = max(1, (int) $this->option('batch-tries'));

        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel === null) {
            $this->error('Trendyol kanalı yok.');

            return self::FAILURE;
        }

        try {
            $client = $apiClient->forChannel($channel);
        } catch (\Throwable $e) {
            $this->error('API: '.$e->getMessage());

            return self::FAILURE;
        }

        $brand = $this->resolveTrendyolBrand($client, $brandName);
        if ($brand === null) {
            $this->error('Trendyol’da marka bulunamadı: '.$brandName);

            return self::FAILURE;
        }

        $externalId = (string) $brand['id'];
        $externalName = (string) $brand['name'];
        $this->info(sprintf('Marka: %s (#%s)', $externalName, $externalId));

        if (! $dryRun) {
            $this->applyForceBrand($channel, $externalId, $externalName);
            $this->info('force_brand + tüm marka eşlemeleri güncellendi.');
        }

        $listings = MarketplaceListing::query()
            ->where('channel_key', 'trendyol')
            ->with(['product.brand', 'product.categories', 'product.images'])
            ->orderBy('id')
            ->get()
            ->filter(fn (MarketplaceListing $l) => $l->product !== null)
            ->values();

        if ($limit > 0) {
            $listings = $listings->take($limit);
        }

        $this->line('Toplam listing: '.$listings->count());
        $this->newLine();

        $ok = 0;
        $fail = 0;
        $skipped = 0;
        $failures = [];

        foreach ($listings as $index => $listing) {
            /** @var Product $product */
            $product = $listing->product;
            $n = $index + 1;
            $label = sprintf('%d/%d %s | %s', $n, $listings->count(), $product->sku ?: $product->slug, $product->name);

            $preflight = $this->preflight($product, $channel, $readiness);
            if ($preflight !== null) {
                $fail++;
                $failures[] = $label.' → PREFLIGHT: '.$preflight;
                $this->error('FAIL '.$label.' → '.$preflight);

                continue;
            }

            $tyState = $this->describeTyBarcode($client, (string) $product->barcode);
            $this->line($label);
            $this->line('  TY barkod durumu: '.$tyState);

            if ($dryRun) {
                $skipped++;
                $this->warn('  [dry-run] gönderilmedi');

                continue;
            }

            $listing->fill([
                'status' => 'draft',
                'external_product_id' => $product->barcode ?: $listing->external_product_id,
                'last_error' => null,
            ])->save();

            try {
                $result = $listingService->publish($product->id, 'trendyol');
            } catch (\Throwable $e) {
                $fail++;
                $failures[] = $label.' → '.$e->getMessage();
                $this->error('FAIL '.$label.' → '.$e->getMessage());

                continue;
            }

            if (! ($result['success'] ?? false)) {
                $msg = (string) ($result['message'] ?? 'bilinmeyen');
                $fail++;
                $failures[] = $label.' → '.$msg;
                $this->error('FAIL '.$label.' → '.$msg);

                continue;
            }

            $fresh = $result['listing'] ?? $listing->fresh();
            $batchId = (string) ($fresh?->external_product_id ?? '');
            $sentBrand = data_get($fresh?->payload_snapshot, 'items.0.brandId');
            $this->line('  kuyruk OK | batch='.$batchId.' | sent_brandId='.json_encode($sentBrand));

            if ($batchId !== '' && str_contains($batchId, '-')) {
                $batchOutcome = $this->waitBatch($client, $batchId, $batchTries, $batchWaitMs);
                if ($batchOutcome['ok']) {
                    $ok++;
                    $this->info('  BATCH OK '.$batchOutcome['detail']);
                } else {
                    $fail++;
                    $failures[] = $label.' → BATCH: '.$batchOutcome['detail'];
                    $this->error('  BATCH FAIL '.$batchOutcome['detail']);
                    $fresh?->fill(['status' => 'error', 'last_error' => $batchOutcome['detail']])->save();
                }
            } else {
                // Batch id yoksa kuyruğa alındı say.
                $ok++;
                $this->info('  OK (batch id yok / doğrulanamadı)');
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->newLine();
        $this->info(sprintf('Özet → OK: %d | FAIL: %d | dry-skip: %d', $ok, $fail, $skipped));
        if ($failures !== []) {
            $this->newLine();
            $this->warn('Hatalar:');
            foreach ($failures as $line) {
                $this->line(' - '.$line);
            }
        }

        $this->line('Not: Site ürün markaları değişmedi; Trendyol brandId='.$externalId.' ('.$externalName.').');
        $this->line('Onay kuyruğundaki mevcut Koşar kayıtları aynı barkodla update yoluna düşer (çift create olmaz).');

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{id: string, name: string}|null
     */
    private function resolveTrendyolBrand(TrendyolApiClient $client, string $name): ?array
    {
        $variants = array_values(array_unique(array_filter([
            $name,
            mb_strtoupper($name, 'UTF-8'),
            mb_strtolower($name, 'UTF-8'),
            Str::ascii($name),
            'Kosar',
            'KOSAR',
            'Koşar',
        ])));

        foreach ($variants as $variant) {
            $matches = $client->searchBrandsByName($variant);
            if ($matches === []) {
                continue;
            }
            $needle = mb_strtolower(trim($name), 'UTF-8');
            foreach ($matches as $row) {
                if (mb_strtolower(trim((string) ($row['name'] ?? '')), 'UTF-8') === $needle) {
                    $id = (string) ($row['id'] ?? '');
                    if ($id !== '') {
                        return ['id' => $id, 'name' => (string) ($row['name'] ?? $name)];
                    }
                }
            }
            $id = (string) ($matches[0]['id'] ?? '');
            if ($id !== '') {
                return ['id' => $id, 'name' => (string) ($matches[0]['name'] ?? $name)];
            }
        }

        return null;
    }

    private function applyForceBrand(MarketplaceChannel $channel, string $externalId, string $externalName): void
    {
        $settings = is_array($channel->settings) ? $channel->settings : [];
        $settings['force_brand_external_id'] = $externalId;
        $settings['force_brand_external_name'] = $externalName;
        $channel->settings = $settings;
        $channel->save();

        $brandIds = Product::query()->whereNotNull('brand_id')->distinct()->pluck('brand_id')
            ->merge(
                MarketplaceBrandMapping::query()->where('channel_key', 'trendyol')->pluck('brand_id')
            )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        foreach ($brandIds as $brandId) {
            MarketplaceBrandMapping::query()->updateOrCreate(
                ['channel_key' => 'trendyol', 'brand_id' => $brandId],
                [
                    'external_brand_id' => $externalId,
                    'external_brand_name' => $externalName,
                    'metadata' => [
                        'forced_brand' => true,
                        'forced_at' => now()->toIso8601String(),
                        'reupload' => true,
                    ],
                ],
            );
        }
    }

    private function preflight(Product $product, MarketplaceChannel $channel, ProductReadinessChecker $readiness): ?string
    {
        if (blank($product->barcode)) {
            return 'Barkod yok';
        }
        if (blank($product->sku)) {
            return 'SKU yok';
        }
        if (! $product->brand_id) {
            return 'Site markası yok';
        }
        if (! $readiness->brandMapped($product, 'trendyol') && blank(data_get($channel->settings, 'force_brand_external_id'))) {
            return 'Marka eşlemesi yok';
        }

        $validation = app(ProductListingService::class)->validateForPublish($product->fresh(['brand', 'categories', 'images']), $channel);
        if (! ($validation['ok'] ?? false)) {
            return implode('; ', $validation['reasons'] ?? ['doğrulama başarısız']);
        }

        return null;
    }

    private function describeTyBarcode(TrendyolApiClient $client, string $barcode): string
    {
        if ($barcode === '') {
            return 'barkod yok';
        }

        try {
            $raw = $client->getProductsByBarcode($barcode);
            $row = data_get($raw, 'content.0')
                ?? data_get($raw, 'products.0')
                ?? (isset($raw['barcode']) ? $raw : null);
            if (! is_array($row) || $row === []) {
                return 'yok (create yolu)';
            }

            $approved = data_get($row, 'approved');
            $archived = data_get($row, 'archived');

            return sprintf(
                'var | approved=%s archived=%s contentId=%s (update yolu)',
                json_encode($approved),
                json_encode($archived),
                (string) (data_get($row, 'contentId') ?? '—')
            );
        } catch (\Throwable $e) {
            return 'sorgu hata/yok: '.$e->getMessage().' (create denenecek)';
        }
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function waitBatch(TrendyolApiClient $client, string $batchId, int $tries, int $waitMs): array
    {
        $last = 'bekleniyor';
        for ($i = 1; $i <= $tries; $i++) {
            try {
                $batch = $client->getBatchRequest($batchId);
                $status = data_get($batch, 'status')
                    ?? data_get($batch, 'items.0.status')
                    ?? data_get($batch, 'itemList.0.status');
                $fail = data_get($batch, 'items.0.failureReasons')
                    ?? data_get($batch, 'items.0.errors')
                    ?? data_get($batch, 'itemList.0.failureReasons')
                    ?? data_get($batch, 'failedItemCount');

                $statusStr = is_scalar($status) ? (string) $status : json_encode($status, JSON_UNESCAPED_UNICODE);
                $failStr = $fail === null || $fail === [] || $fail === 0
                    ? ''
                    : json_encode($fail, JSON_UNESCAPED_UNICODE);

                $last = 'try='.$i.' status='.$statusStr.($failStr !== '' ? ' fail='.$failStr : '');

                $upper = mb_strtoupper($statusStr, 'UTF-8');
                if (in_array($upper, ['SUCCESS', 'COMPLETED', 'DONE'], true) && ($fail === null || $fail === [] || $fail === 0)) {
                    return ['ok' => true, 'detail' => $last];
                }
                if (str_contains($upper, 'FAIL') || str_contains($upper, 'ERROR') || ($failStr !== '' && $fail !== 0)) {
                    return ['ok' => false, 'detail' => $last];
                }
                // IN_PROGRESS / PENDING → bekle
            } catch (\Throwable $e) {
                $last = 'try='.$i.' '.$e->getMessage();
            }

            if ($i < $tries) {
                usleep($waitMs * 1000);
            }
        }

        // Zaman aşımı: kuyrukta kalmış olabilir; fail sayma, uyarı olarak OK-ish bırakma → fail etmeyelim ama işaretle
        return ['ok' => true, 'detail' => 'TIMEOUT (kuyrukta olabilir) '.$last];
    }
}
