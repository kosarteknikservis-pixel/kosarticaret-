<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\ProductListingService;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ForceTrendyolBrandCommand extends Command
{
    protected $signature = 'marketplace:trendyol-force-brand
        {brand=Koşar : Trendyol’da aranacak marka adı}
        {--dry-run : API/DB yazmadan planı göster}
        {--skip-publish : Eşleştirmeyi güncelle, ürünleri yeniden gönderme}
        {--limit=0 : Yeniden gönderilecek maksimum listing (0=hepsi)}
        {--sleep-ms=1200 : Listing’ler arası bekleme (ms)}';

    protected $description = 'Trendyol marka eşleştirmelerini tek markaya çeker ve listing’leri içerik güncellemesiyle yeniden gönderir.';

    public function handle(TrendyolApiClient $apiClient, ProductListingService $listingService): int
    {
        $brandName = trim((string) $this->argument('brand'));
        $dryRun = (bool) $this->option('dry-run');
        $skipPublish = (bool) $this->option('skip-publish');
        $limit = max(0, (int) $this->option('limit'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));

        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel === null) {
            $this->error('Trendyol kanalı bulunamadı.');

            return self::FAILURE;
        }

        try {
            $client = $apiClient->forChannel($channel);
        } catch (\Throwable $e) {
            $this->error('Trendyol API: '.$e->getMessage());

            return self::FAILURE;
        }

        $matches = $this->searchBrand($client, $brandName);
        if ($matches === []) {
            $this->error('Trendyol’da “'.$brandName.'” markası bulunamadı. Satıcı panelinde marka onayı gerekebilir.');

            return self::FAILURE;
        }

        $chosen = $this->pickExactOrFirst($matches, $brandName);
        $externalId = (string) ($chosen['id'] ?? '');
        $externalName = (string) ($chosen['name'] ?? $brandName);
        if ($externalId === '') {
            $this->error('Trendyol marka yanıtında id yok.');

            return self::FAILURE;
        }

        $this->info(sprintf('Hedef marka: %s (#%s)', $externalName, $externalId));

        $brandIds = Product::query()
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $mappedBrandIds = MarketplaceBrandMapping::query()
            ->where('channel_key', 'trendyol')
            ->pluck('brand_id')
            ->map(fn ($id) => (int) $id);

        $targetBrandIds = $brandIds->merge($mappedBrandIds)->unique()->values();
        if ($targetBrandIds->isEmpty()) {
            $this->warn('Eşlenecek site markası yok.');
        }

        $this->line('Güncellenecek site markası sayısı: '.$targetBrandIds->count());

        if (! $dryRun) {
            $settings = is_array($channel->settings) ? $channel->settings : [];
            $settings['force_brand_external_id'] = $externalId;
            $settings['force_brand_external_name'] = $externalName;
            $channel->settings = $settings;
            $channel->save();

            foreach ($targetBrandIds as $brandId) {
                MarketplaceBrandMapping::query()->updateOrCreate(
                    [
                        'channel_key' => 'trendyol',
                        'brand_id' => $brandId,
                    ],
                    [
                        'external_brand_id' => $externalId,
                        'external_brand_name' => $externalName,
                        'metadata' => [
                            'forced_brand' => true,
                            'forced_at' => now()->toIso8601String(),
                            'forced_name' => $brandName,
                        ],
                    ],
                );
            }

            $this->info('Marka eşleştirmeleri ve kanal force_brand ayarı güncellendi.');
        } else {
            $this->warn('Dry-run: eşleştirme/ayar yazılmadı.');
        }

        if ($skipPublish) {
            $this->info('Publish atlandı (--skip-publish).');

            return self::SUCCESS;
        }

        $listings = MarketplaceListing::query()
            ->where('channel_key', 'trendyol')
            ->whereIn('status', ['published', 'pending', 'error', 'draft', 'rejected'])
            ->with(['product.brand', 'product.categories', 'product.images'])
            ->orderBy('id')
            ->get();

        if ($limit > 0) {
            $listings = $listings->take($limit);
        }

        $this->line('Yeniden gönderilecek listing: '.$listings->count());

        $ok = 0;
        $fail = 0;

        foreach ($listings as $listing) {
            $product = $listing->product;
            if ($product === null) {
                $this->warn('Listing #'.$listing->id.' ürünsüz, atlandı.');
                $fail++;

                continue;
            }

            $label = ($product->sku ?: $product->slug).' | '.$product->name;
            if ($dryRun) {
                $this->line('[dry-run] '.$label);

                continue;
            }

            // publishTrendyol: published + temiz external_id → yalnız stok/fiyat.
            // Marka için içerik yoluna düşmesi için draft’a çek.
            $listing->fill([
                'status' => 'draft',
                'external_product_id' => $product->barcode ?: $listing->external_product_id,
                'last_error' => null,
            ])->save();

            try {
                $result = $listingService->publish($product->id, 'trendyol');
                if ($result['success'] ?? false) {
                    $ok++;
                    $this->info('OK '.$label);
                } else {
                    $fail++;
                    $this->error('FAIL '.$label.' → '.($result['message'] ?? 'bilinmeyen'));
                }
            } catch (\Throwable $e) {
                $fail++;
                $this->error('FAIL '.$label.' → '.$e->getMessage());
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run tamam. Publish yapılmadı.');
        } else {
            $this->info(sprintf('Bitti. Başarılı: %d, Hata: %d', $ok, $fail));
        }

        // Site markasını değiştirmedik; yalnız Trendyol brandId.
        $this->line('Not: Site ürün markaları (Sumak/Pedrollo vb.) değişmedi; yalnızca Trendyol marka alanı.');

        return $fail > 0 && ! $dryRun ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<array{id?: int|string, name?: string}>
     */
    private function searchBrand(TrendyolApiClient $client, string $name): array
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
            if ($matches !== []) {
                return $matches;
            }
        }

        return [];
    }

    /**
     * @param  list<array{id?: int|string, name?: string}>  $matches
     * @return array{id?: int|string, name?: string}
     */
    private function pickExactOrFirst(array $matches, string $name): array
    {
        $needle = mb_strtolower(trim($name), 'UTF-8');
        foreach ($matches as $row) {
            if (mb_strtolower(trim((string) ($row['name'] ?? '')), 'UTF-8') === $needle) {
                return $row;
            }
        }

        return $matches[0];
    }
}
