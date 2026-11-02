<?php

namespace App\Console\Commands;

use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\ProductListingService;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReuploadTrendyolNewBarcodesCommand extends Command
{
    protected $signature = 'marketplace:trendyol-reupload-new-barcodes
        {--brand=Koşar : Trendyol markası}
        {--dry-run : Göndermeden planı göster}
        {--include-unapproved : Onay kuyruğundakileri de yeni barkodla gönder}
        {--sleep-ms=2200 : Ürünler arası bekleme}
        {--batch-wait-ms=2500 : Batch sorgu aralığı}
        {--batch-tries=10 : Batch sorgu denemesi}';

    protected $description = 'Arşivdeki Trendyol ürünlerini yeni barkod + Koşar markasıyla create eder (site barkodu değişmez).';

    /** @var list<string> */
    private array $reservedBarcodes = [];

    public function handle(TrendyolApiClient $apiClient, ProductListingService $listingService): int
    {
        $brandName = trim((string) $this->option('brand'));
        $dryRun = (bool) $this->option('dry-run');
        $includeUnapproved = (bool) $this->option('include-unapproved');
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
            $this->error('Trendyol markası bulunamadı: '.$brandName);

            return self::FAILURE;
        }

        $this->info(sprintf('Marka: %s (#%s)', $brand['name'], $brand['id']));

        if (! $dryRun) {
            $this->applyForceBrand($channel, $brand['id'], $brand['name']);
        }

        $listings = MarketplaceListing::query()
            ->where('channel_key', 'trendyol')
            ->with(['product.brand', 'product.categories', 'product.images'])
            ->orderBy('id')
            ->get()
            ->filter(fn (MarketplaceListing $l) => $l->product !== null)
            ->values();

        $this->reservedBarcodes = array_values(array_unique(array_filter(array_merge(
            Product::query()->whereNotNull('barcode')->pluck('barcode')->all(),
            MarketplaceListing::query()->whereNotNull('external_barcode')->pluck('external_barcode')->all(),
        ))));

        $targets = [];
        $skipped = [];

        foreach ($listings as $listing) {
            $product = $listing->product;
            $oldBarcode = trim((string) ($product->barcode ?? ''));
            $state = $this->tyState($client, $oldBarcode);

            $isArchived = ($state['approved'] === true && $state['archived'] === true);
            $isUnapproved = ($state['approved'] === false && $state['exists']);
            $isMissing = ! $state['exists'];

            if ($isArchived || $isMissing || ($includeUnapproved && $isUnapproved)) {
                $targets[] = ['listing' => $listing, 'state' => $state, 'old' => $oldBarcode];
            } else {
                $skipped[] = sprintf(
                    '%s | approved=%s archived=%s (atlandı)',
                    $product->sku ?: $product->slug,
                    json_encode($state['approved']),
                    json_encode($state['archived'])
                );
            }
        }

        $count = count($targets);
        $this->line('Hedef: '.$count);
        $this->line('Atlanan: '.count($skipped));
        foreach ($skipped as $s) {
            $this->line('  skip '.$s);
        }
        $this->newLine();

        if ($targets === []) {
            $this->warn('Gönderilecek ürün yok.');

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
            $label = sprintf('%d/%d %s | %s', $n, $count, $product->sku ?: $product->slug, $product->name);

            $newBarcode = $this->allocateBarcode($client);
            $this->line($label);
            $this->line(sprintf('  old=%s → new=%s | ty=%s', $row['old'] ?: '—', $newBarcode, $row['state']['label']));

            if ($dryRun) {
                $this->warn('  [dry-run]');

                continue;
            }

            $validation = $listingService->validateForPublish(
                $product->fresh(['brand', 'categories', 'images']),
                $channel
            );
            if (! ($validation['ok'] ?? false)) {
                $msg = implode('; ', $validation['reasons'] ?? ['doğrulama başarısız']);
                $fail++;
                $failures[] = $label.' → PREFLIGHT: '.$msg;
                $this->error('  FAIL '.$msg);

                continue;
            }

            // Create yolu için yeni barkod; eski arşiv kaydına update düşmesin.
            $listing->fill([
                'external_barcode' => $newBarcode,
                'status' => 'draft',
                'external_product_id' => $newBarcode,
                'last_error' => null,
            ])->save();
            $listing->refresh();

            // Çift kontrol: yeni barkod TY'de yok.
            try {
                $probe = $client->getProductsByBarcode($newBarcode);
                $exists = data_get($probe, 'content.0')
                    ?? data_get($probe, 'products.0')
                    ?? (isset($probe['barcode']) ? $probe : null);
                if (is_array($exists) && $exists !== []) {
                    $fail++;
                    $failures[] = $label.' → yeni barkod TY’de zaten var: '.$newBarcode;
                    $this->error('  FAIL yeni barkod çakışması');

                    continue;
                }
            } catch (\Throwable) {
                // yok = iyi
            }

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
            $sentBarcode = data_get($fresh?->payload_snapshot, 'items.0.barcode');
            $sentBrand = data_get($fresh?->payload_snapshot, 'items.0.brandId');
            $batchId = (string) ($fresh?->external_product_id ?? '');

            if ((string) $sentBarcode !== $newBarcode) {
                $fail++;
                $failures[] = $label.' → gönderilen barkod uyuşmuyor: '.json_encode($sentBarcode);
                $this->error('  FAIL barkod uyuşmazlığı');

                continue;
            }
            if ((string) $sentBrand !== (string) $brand['id']) {
                $fail++;
                $failures[] = $label.' → brandId uyuşmuyor: '.json_encode($sentBrand);
                $this->error('  FAIL brand uyuşmazlığı');

                continue;
            }

            $this->line('  kuyruk OK | batch='.$batchId.' | brand='.$sentBrand.' | barcode='.$sentBarcode);

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

            // Yeni barkod canlıda görünsün (unapproved da olsa).
            try {
                $verify = $client->getProductsByBarcode($newBarcode);
                $vrow = data_get($verify, 'content.0')
                    ?? data_get($verify, 'products.0')
                    ?? (isset($verify['barcode']) ? $verify : null);
                if (is_array($vrow) && $vrow !== []) {
                    $this->info('  VERIFY OK TY’de yeni barkod görünüyor | approved='.json_encode(data_get($vrow, 'approved')));
                } else {
                    $this->warn('  VERIFY: henüz listede yok (onay kuyruğunda olabilir)');
                }
            } catch (\Throwable $e) {
                $this->warn('  VERIFY: '.$e->getMessage());
            }

            $ok++;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->newLine();
        $this->info(sprintf('Özet → OK: %d | FAIL: %d | atlanan: %d', $ok, $fail, count($skipped)));
        if ($failures !== []) {
            $this->warn('Hatalar:');
            foreach ($failures as $line) {
                $this->line(' - '.$line);
            }
        }
        $this->line('Site product.barcode değiştirilmedi; Trendyol external_barcode kullanıldı.');

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{exists: bool, approved: ?bool, archived: ?bool, label: string}
     */
    private function tyState(TrendyolApiClient $client, string $barcode): array
    {
        if ($barcode === '') {
            return ['exists' => false, 'approved' => null, 'archived' => null, 'label' => 'barkod yok'];
        }

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
            return ['exists' => false, 'approved' => null, 'archived' => null, 'label' => 'sorgu/yok: '.$e->getMessage()];
        }
    }

    private function allocateBarcode(TrendyolApiClient $client): string
    {
        $max = 2000000028000;
        foreach ($this->reservedBarcodes as $code) {
            if (preg_match('/^\d{12,14}$/', $code)) {
                $max = max($max, (int) substr($code, 0, 12));
            }
        }

        for ($i = 1; $i <= 5000; $i++) {
            $body12 = (string) ($max + $i);
            if (strlen($body12) > 12) {
                $body12 = substr($body12, -12);
            }
            $body12 = str_pad($body12, 12, '0', STR_PAD_LEFT);
            $candidate = $body12.$this->ean13CheckDigit($body12);

            if (in_array($candidate, $this->reservedBarcodes, true)) {
                continue;
            }

            try {
                $probe = $client->getProductsByBarcode($candidate);
                $exists = data_get($probe, 'content.0')
                    ?? data_get($probe, 'products.0')
                    ?? (isset($probe['barcode']) ? $probe : null);
                if (is_array($exists) && $exists !== []) {
                    $this->reservedBarcodes[] = $candidate;

                    continue;
                }
            } catch (\Throwable) {
                // müsait
            }

            $this->reservedBarcodes[] = $candidate;

            return $candidate;
        }

        throw new \RuntimeException('Yeni barkod üretilemedi.');
    }

    private function ean13CheckDigit(string $digits12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $n = (int) $digits12[$i];
            $sum += ($i % 2 === 0) ? $n : $n * 3;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * @return array{id: string, name: string}|null
     */
    private function resolveTrendyolBrand(TrendyolApiClient $client, string $name): ?array
    {
        foreach (array_unique(array_filter([$name, 'Koşar', 'Kosar', 'KOSAR', Str::ascii($name)])) as $variant) {
            $matches = $client->searchBrandsByName((string) $variant);
            if ($matches === []) {
                continue;
            }
            foreach ($matches as $row) {
                if (mb_strtolower(trim((string) ($row['name'] ?? '')), 'UTF-8') === mb_strtolower(trim($name), 'UTF-8')) {
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

        $brandIds = Product::query()->whereNotNull('brand_id')->pluck('brand_id')
            ->merge(MarketplaceBrandMapping::query()->where('channel_key', 'trendyol')->pluck('brand_id'))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();

        foreach ($brandIds as $brandId) {
            MarketplaceBrandMapping::query()->updateOrCreate(
                ['channel_key' => 'trendyol', 'brand_id' => $brandId],
                [
                    'external_brand_id' => $externalId,
                    'external_brand_name' => $externalName,
                    'metadata' => ['forced_brand' => true, 'new_barcode_reupload' => true, 'at' => now()->toIso8601String()],
                ],
            );
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

        return ['ok' => true, 'detail' => 'TIMEOUT (kuyrukta olabilir) '.$last];
    }
}
