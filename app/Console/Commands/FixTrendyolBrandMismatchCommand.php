<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\ProductListingService;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use App\Services\Marketplace\Trendyol\TrendyolListingPayloadBuilder;
use Illuminate\Console\Command;

class FixTrendyolBrandMismatchCommand extends Command
{
    protected $signature = 'marketplace:trendyol-fix-brand-mismatch
        {--dry-run : Göndermeden başlık önizle}
        {--all-listings : Yalnız onay/revize değil, tüm Trendyol listing’ler}
        {--sleep-ms=2000}
        {--batch-wait-ms=2500}
        {--batch-tries=8}';

    protected $description = 'Trendyol Hatalı Marka Bilgisi: başlık/açıklamadan üretici markayı temizleyip unapproved update gönderir.';

    private const FORBIDDEN = ['sumak', 'kaysu', 'pedrollo', 'winpo', 'troy', 'renato', 'ardonat', 'horoz'];

    public function handle(
        TrendyolApiClient $apiClient,
        ProductListingService $listingService,
        TrendyolListingPayloadBuilder $payloadBuilder,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $allListings = (bool) $this->option('all-listings');
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $batchWaitMs = max(500, (int) $this->option('batch-wait-ms'));
        $batchTries = max(1, (int) $this->option('batch-tries'));

        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel === null) {
            $this->error('Trendyol kanalı yok.');

            return self::FAILURE;
        }

        $forceName = trim((string) data_get($channel->settings, 'force_brand_external_name', 'Koşar'));
        $forceId = trim((string) data_get($channel->settings, 'force_brand_external_id', ''));
        if ($forceId === '') {
            $this->error('force_brand_external_id yok; önce Koşar force ayarı gerekir.');

            return self::FAILURE;
        }

        try {
            $client = $apiClient->forChannel($channel);
        } catch (\Throwable $e) {
            $this->error('API: '.$e->getMessage());

            return self::FAILURE;
        }

        $listings = MarketplaceListing::query()
            ->where('channel_key', 'trendyol')
            ->with(['product.brand', 'product.categories', 'product.images'])
            ->orderBy('id')
            ->get()
            ->filter(fn (MarketplaceListing $l) => $l->product !== null)
            ->values();

        $targets = [];
        foreach ($listings as $listing) {
            $product = $listing->product;
            $barcode = $listing->resolveBarcode();
            if ($barcode === '') {
                $this->warn('Barkodsuz atlandı: '.($product->sku ?: $product->id));

                continue;
            }

            $ty = $this->tyState($client, $barcode);
            // Varsayılan: onay/revize kuyruğu (approved=false). Arşiv/aktife dokunma.
            if (! $allListings && ! ($ty['exists'] && $ty['approved'] === false)) {
                continue;
            }

            $previewTitle = $payloadBuilder->stripForeignBrandNames((string) $product->name, $forceName ?: 'Koşar');
            $rawHasForbidden = $this->containsForbidden((string) $product->name);
            $cleanHasForbidden = $this->containsForbidden($previewTitle);

            $targets[] = compact('listing', 'ty', 'previewTitle', 'rawHasForbidden', 'cleanHasForbidden', 'barcode');
        }

        $this->info('force_brand: '.$forceName.' (#'.$forceId.')');
        $this->line('Hedef (onay/revize kuyruğu): '.count($targets));
        $this->newLine();

        if ($targets === []) {
            $this->warn('Düzeltilecek ürün yok.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $failures = [];

        foreach ($targets as $i => $row) {
            /** @var MarketplaceListing $listing */
            $listing = $row['listing'];
            $product = $listing->product;
            $n = $i + 1;
            $label = sprintf('%d/%d %s | %s', $n, count($targets), $product->sku ?: $product->slug, $product->name);

            $this->line($label);
            $this->line('  barcode='.$row['barcode'].' | ty='.$row['ty']['label']);
            $this->line('  title_clean='.$row['previewTitle']);

            if ($row['cleanHasForbidden']) {
                $fail++;
                $failures[] = $label.' → temiz başlıkta hâlâ yasaklı marka var';
                $this->error('  FAIL temiz başlık yetersiz');

                continue;
            }

            if ($dryRun) {
                $this->warn('  [dry-run]');

                continue;
            }

            $listing->fill([
                'status' => 'draft',
                'external_product_id' => $row['barcode'],
                'last_error' => null,
            ])->save();

            try {
                $result = $listingService->publish($product->id, 'trendyol');
            } catch (\Throwable $e) {
                $fail++;
                $failures[] = $label.' → '.$e->getMessage();
                $this->error('  FAIL '.$e->getMessage());

                continue;
            }

            if (! ($result['success'] ?? false)) {
                $msg = (string) ($result['message'] ?? 'bilinmeyen');
                $fail++;
                $failures[] = $label.' → '.$msg;
                $this->error('  FAIL '.$msg);

                continue;
            }

            $fresh = $result['listing'] ?? $listing->fresh();
            $sentTitle = (string) data_get($fresh?->payload_snapshot, 'items.0.title', '');
            $sentBrand = data_get($fresh?->payload_snapshot, 'items.0.brandId');
            $sentBarcode = (string) data_get($fresh?->payload_snapshot, 'items.0.barcode', '');
            $batchId = (string) ($fresh?->external_product_id ?? '');

            if ($this->containsForbidden($sentTitle)) {
                $fail++;
                $failures[] = $label.' → gönderilen başlıkta yasaklı marka: '.$sentTitle;
                $this->error('  FAIL sent title hâlâ yasaklı marka içeriyor');

                continue;
            }
            if ((string) $sentBrand !== $forceId) {
                $fail++;
                $failures[] = $label.' → brandId='.json_encode($sentBrand);
                $this->error('  FAIL brandId');

                continue;
            }
            if ($sentBarcode !== $row['barcode']) {
                $fail++;
                $failures[] = $label.' → barcode mismatch';
                $this->error('  FAIL barcode');

                continue;
            }

            $this->line('  kuyruk OK | brand='.$sentBrand.' | title='.$sentTitle);

            if ($batchId !== '' && str_contains($batchId, '-')) {
                $batch = $this->waitBatch($client, $batchId, $batchTries, $batchWaitMs);
                if (! $batch['ok']) {
                    $fail++;
                    $failures[] = $label.' → BATCH: '.$batch['detail'];
                    $this->error('  BATCH FAIL '.$batch['detail']);
                    $fresh?->fill(['status' => 'error', 'last_error' => $batch['detail']])->save();

                    continue;
                }
                $this->info('  BATCH OK '.$batch['detail']);
            }

            $ok++;
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->newLine();
        $this->info(sprintf('Özet → OK: %d | FAIL: %d', $ok, $fail));
        if ($failures !== []) {
            $this->warn('Hatalar:');
            foreach ($failures as $line) {
                $this->line(' - '.$line);
            }
        }
        $this->line('Site ürün adları değişmedi; yalnız Trendyol title/description temizlendi.');
        $this->warn('Görselde logo varsa panelden değiştirmeniz gerekir — API görsel logosunu silemez.');

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function containsForbidden(string $text): bool
    {
        $hay = mb_strtolower($text, 'UTF-8');
        foreach (self::FORBIDDEN as $word) {
            if (str_contains($hay, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{exists: bool, approved: ?bool, archived: ?bool, label: string}
     */
    private function tyState(TrendyolApiClient $client, string $barcode): array
    {
        try {
            $raw = $client->getProductsByBarcode($barcode);
            $row = data_get($raw, 'content.0')
                ?? data_get($raw, 'products.0')
                ?? (isset($raw['barcode']) ? $raw : null);
            if (! is_array($row) || $row === []) {
                return ['exists' => false, 'approved' => null, 'archived' => null, 'label' => 'yok'];
            }
            $approved = data_get($row, 'approved');
            $archived = data_get($row, 'archived');
            $approvedBool = is_bool($approved) ? $approved : ($approved === null ? null : (bool) $approved);
            $archivedBool = is_bool($archived) ? $archived : ($archived === null ? null : (bool) $archived);

            return [
                'exists' => true,
                'approved' => $approvedBool,
                'archived' => $archivedBool,
                'label' => sprintf('approved=%s archived=%s', json_encode($approvedBool), json_encode($archivedBool)),
            ];
        } catch (\Throwable $e) {
            return ['exists' => false, 'approved' => null, 'archived' => null, 'label' => 'hata: '.$e->getMessage()];
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
                $failStr = ($fail === null || $fail === [] || $fail === 0) ? '' : json_encode($fail, JSON_UNESCAPED_UNICODE);
                $last = 'try='.$i.' status='.$statusStr.($failStr !== '' ? ' fail='.$failStr : '');
                $upper = mb_strtoupper($statusStr, 'UTF-8');
                if (in_array($upper, ['SUCCESS', 'COMPLETED', 'DONE'], true) && $failStr === '') {
                    return ['ok' => true, 'detail' => $last];
                }
                if (str_contains($upper, 'FAIL') || str_contains($upper, 'ERROR') || $failStr !== '') {
                    return ['ok' => false, 'detail' => $last];
                }
            } catch (\Throwable $e) {
                $last = 'try='.$i.' '.$e->getMessage();
            }
            if ($i < $tries) {
                usleep($waitMs * 1000);
            }
        }

        return ['ok' => true, 'detail' => 'TIMEOUT '.$last];
    }
}
