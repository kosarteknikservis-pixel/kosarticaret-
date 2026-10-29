<?php

namespace App\Console\Commands;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\Hepsiburada\HepsiburadaApiClient;
use Illuminate\Console\Command;
use Throwable;

class CompareMarketplaceChannelsCommand extends Command
{
    protected $signature = 'marketplace:compare-ty-hb {--hb-api : HB API durum sayımlarını da çek}';

    protected $description = 'Trendyol vs Hepsiburada listing farklarını listeler.';

    public function handle(HepsiburadaApiClient $apiClient): int
    {
        $ty = MarketplaceListing::query()
            ->with(['product.brand'])
            ->where('channel_key', 'trendyol')
            ->get()
            ->keyBy('product_id');

        $hb = MarketplaceListing::query()
            ->with(['product.brand'])
            ->where('channel_key', 'hepsiburada')
            ->get()
            ->keyBy('product_id');

        $this->info(sprintf(
            'DB listings: trendyol=%d hepsiburada=%d',
            $ty->count(),
            $hb->count()
        ));

        $this->line('--- TY status ---');
        foreach ($ty->groupBy('status') as $status => $rows) {
            $this->line($status.': '.$rows->count());
        }

        $this->line('--- HB status ---');
        foreach ($hb->groupBy('status') as $status => $rows) {
            $this->line($status.': '.$rows->count());
        }

        $tyOnly = $ty->keys()->diff($hb->keys())->values();
        $hbOnly = $hb->keys()->diff($ty->keys())->values();
        $both = $ty->keys()->intersect($hb->keys())->values();

        $this->info('Her iki kanalda: '.$both->count());
        $this->info('Sadece Trendyol: '.$tyOnly->count());
        $this->info('Sadece Hepsiburada: '.$hbOnly->count());

        $this->line('=== SADECE TRENDYOL ===');
        foreach ($tyOnly as $productId) {
            $listing = $ty->get($productId);
            $product = $listing?->product;
            $this->line(sprintf(
                'TY_ONLY | #%s | %s | %s | brand=%s | status=%s | price=%s | err=%s',
                $productId,
                (string) ($product?->sku ?: '-'),
                (string) ($product?->slug ?: '-'),
                (string) ($product?->brand?->name ?: '-'),
                (string) ($listing?->status ?: '-'),
                (string) ($listing?->channel_price ?: '-'),
                mb_substr((string) ($listing?->last_error ?: ''), 0, 120)
            ));
        }

        $this->line('=== SADECE HEPSIBURADA ===');
        foreach ($hbOnly as $productId) {
            $listing = $hb->get($productId);
            $product = $listing?->product;
            $this->line(sprintf(
                'HB_ONLY | #%s | %s | %s | brand=%s | status=%s | price=%s | err=%s',
                $productId,
                (string) ($product?->sku ?: '-'),
                (string) ($product?->slug ?: '-'),
                (string) ($product?->brand?->name ?: '-'),
                (string) ($listing?->status ?: '-'),
                (string) ($listing?->channel_price ?: '-'),
                mb_substr((string) ($listing?->last_error ?: ''), 0, 120)
            ));
        }

        $this->line('=== İKİSİNDE DE AMA DURUM FARKLI ===');
        foreach ($both as $productId) {
            $a = $ty->get($productId);
            $b = $hb->get($productId);
            if ($a === null || $b === null || $a->status === $b->status) {
                continue;
            }
            $product = $a->product ?? Product::query()->find($productId);
            $this->line(sprintf(
                'DIFF | #%s | %s | ty=%s hb=%s | ty_err=%s | hb_err=%s',
                $productId,
                (string) ($product?->sku ?: '-'),
                (string) $a->status,
                (string) $b->status,
                mb_substr((string) ($a->last_error ?: ''), 0, 80),
                mb_substr((string) ($b->last_error ?: ''), 0, 80)
            ));
        }

        if ($this->option('hb-api')) {
            $channel = MarketplaceChannel::query()->where('key', 'hepsiburada')->first();
            if ($channel === null) {
                $this->warn('HB kanalı yok.');

                return self::SUCCESS;
            }

            try {
                $client = $apiClient->forChannel($channel);
            } catch (Throwable $e) {
                $this->warn('HB API: '.$e->getMessage());

                return self::SUCCESS;
            }

            $this->line('=== HB API products-by-status ===');
            foreach (['MATCHED', 'CREATED', 'WAITING', 'MISSING_INFO', 'REJECTED'] as $status) {
                $total = 0;
                $skus = [];
                for ($page = 0; $page < 10; $page++) {
                    try {
                        $rows = $client->getProductsByStatus($status, $page, 50);
                    } catch (Throwable $e) {
                        $this->warn($status.' page '.$page.': '.$e->getMessage());
                        break;
                    }
                    $data = $rows['data'] ?? [];
                    if (! is_array($data) || $data === []) {
                        break;
                    }
                    $total += count($data);
                    foreach ($data as $row) {
                        if (is_array($row)) {
                            $skus[] = (string) ($row['merchantSku'] ?? $row['sku'] ?? '');
                        }
                    }
                    if (count($data) < 50) {
                        break;
                    }
                }
                $this->info($status.': '.$total);
                if ($skus !== []) {
                    $this->line('  '.implode(', ', array_values(array_filter($skus))));
                }
            }
        }

        return self::SUCCESS;
    }
}
