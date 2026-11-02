<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\Trendyol\TrendyolApiClient;
use Illuminate\Console\Command;

class StatusTrendyolListingsCommand extends Command
{
    protected $signature = 'marketplace:trendyol-status';

    protected $description = 'Tüm Trendyol listing’lerinin yerel + canlı (approved/archived) durumunu özetler.';

    public function handle(TrendyolApiClient $apiClient): int
    {
        $channel = MarketplaceChannel::query()->where('key', 'trendyol')->first();
        if ($channel === null) {
            $this->error('Trendyol kanalı yok.');

            return self::FAILURE;
        }

        $this->info('force_brand: '.((string) data_get($channel->settings, 'force_brand_external_name', '—'))
            .' (#'.((string) data_get($channel->settings, 'force_brand_external_id', '—')).')');

        try {
            $client = $apiClient->forChannel($channel);
        } catch (\Throwable $e) {
            $this->error('API: '.$e->getMessage());

            return self::FAILURE;
        }

        $listings = MarketplaceListing::query()
            ->where('channel_key', 'trendyol')
            ->with('product.brand')
            ->orderBy('id')
            ->get();

        $counts = [
            'local' => $listings->count(),
            'api_ok' => 0,
            'api_missing' => 0,
            'api_error' => 0,
            'approved_archived' => 0,
            'approved_active' => 0,
            'unapproved' => 0,
            'other' => 0,
            'sent_kosar' => 0,
        ];

        $this->newLine();
        foreach ($listings as $listing) {
            $product = $listing->product;
            if ($product === null) {
                $this->warn('Listing #'.$listing->id.' ürünsüz');

                continue;
            }

            $sentBrand = data_get($listing->payload_snapshot, 'items.0.brandId');
            if ((string) $sentBrand === '998047') {
                $counts['sent_kosar']++;
            }

            $barcode = (string) ($product->barcode ?: '');
            $ty = '—';
            $bucket = 'other';

            if ($barcode === '') {
                $ty = 'BARKOD YOK';
                $counts['api_error']++;
            } else {
                try {
                    $raw = $client->getProductsByBarcode($barcode);
                    $row = data_get($raw, 'content.0')
                        ?? data_get($raw, 'products.0')
                        ?? (isset($raw['barcode']) ? $raw : null);

                    if (! is_array($row) || $row === []) {
                        $ty = 'API: KAYIT YOK';
                        $counts['api_missing']++;
                        $bucket = 'missing';
                    } else {
                        $counts['api_ok']++;
                        $approved = (bool) data_get($row, 'approved');
                        $archived = data_get($row, 'archived');
                        $archivedBool = $archived === null ? null : (bool) $archived;
                        $contentId = data_get($row, 'contentId');

                        if ($approved && $archivedBool === true) {
                            $bucket = 'approved_archived';
                            $counts['approved_archived']++;
                        } elseif ($approved && $archivedBool !== true) {
                            $bucket = 'approved_active';
                            $counts['approved_active']++;
                        } elseif (! $approved) {
                            $bucket = 'unapproved';
                            $counts['unapproved']++;
                        } else {
                            $counts['other']++;
                        }

                        $ty = sprintf(
                            'approved=%s archived=%s contentId=%s',
                            $approved ? 'true' : 'false',
                            $archivedBool === null ? 'null' : ($archivedBool ? 'true' : 'false'),
                            $contentId === null ? '—' : (string) $contentId
                        );
                    }
                } catch (\Throwable $e) {
                    $ty = 'API HATA: '.$e->getMessage();
                    $counts['api_error']++;
                }
            }

            $this->line(sprintf(
                '[%s] %s | local=%s | sentBrand=%s | synced=%s | %s',
                $bucket,
                $product->sku ?: $product->slug,
                $listing->status,
                json_encode($sentBrand),
                $listing->last_synced_at?->toDateTimeString() ?? '—',
                $ty
            ));
        }

        $this->newLine();
        $this->info('ÖZET');
        foreach ($counts as $k => $v) {
            $this->line(sprintf('- %s: %d', $k, $v));
        }
        $this->newLine();
        $this->warn('Yorum: approved_archived = panelde Arşiv; unapproved = Onay süreci; approved_active = Aktif.');
        $this->warn('Arşivlilere aynı barkodla update, vitrine yeni ürün getirmez. Yeni barkod + create gerekir.');

        return self::SUCCESS;
    }
}
