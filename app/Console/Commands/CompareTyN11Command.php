<?php

namespace App\Console\Commands;

use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Services\Marketplace\N11\N11ApiClient;
use App\Models\MarketplaceChannel;
use Illuminate\Console\Command;
use Throwable;

class CompareTyN11Command extends Command
{
    protected $signature = 'marketplace:compare-ty-n11 {--inspect-pending : N11 pending listing task detaylarını çek}';

    protected $description = 'Trendyol vs N11 listing sayı ve farklarını listeler.';

    public function handle(N11ApiClient $n11Api): int
    {
        $ty = MarketplaceListing::query()
            ->with(['product.brand'])
            ->where('channel_key', 'trendyol')
            ->get()
            ->keyBy('product_id');

        $n11 = MarketplaceListing::query()
            ->with(['product.brand'])
            ->where('channel_key', 'n11')
            ->get()
            ->keyBy('product_id');

        $this->info(sprintf('DB listings: trendyol=%d n11=%d', $ty->count(), $n11->count()));

        $this->line('--- TY status ---');
        foreach ($ty->groupBy('status') as $status => $rows) {
            $this->line($status.': '.$rows->count());
        }

        $this->line('--- N11 status ---');
        foreach ($n11->groupBy('status') as $status => $rows) {
            $this->line($status.': '.$rows->count());
        }

        $tyActive = $ty->filter(fn ($l) => in_array((string) $l->status, ['pending', 'published', 'approved'], true));
        $n11Active = $n11->filter(fn ($l) => in_array((string) $l->status, ['pending', 'published', 'approved'], true));
        $this->info(sprintf(
            'Aktif sayılan (pending+published+approved): TY=%d N11=%d fark=%d',
            $tyActive->count(),
            $n11Active->count(),
            $tyActive->count() - $n11Active->count()
        ));

        $tyOnly = $ty->keys()->diff($n11->keys())->values();
        $n11Only = $n11->keys()->diff($ty->keys())->values();
        $both = $ty->keys()->intersect($n11->keys())->values();

        $this->info('Her iki kanalda: '.$both->count());
        $this->info('Sadece Trendyol: '.$tyOnly->count());
        $this->info('Sadece N11: '.$n11Only->count());

        $this->line('=== SADECE TRENDYOL (N11 eksik aday) ===');
        foreach ($tyOnly as $productId) {
            $listing = $ty->get($productId);
            $product = $listing?->product;
            $this->line($this->formatRow('TY_ONLY', $productId, $product, $listing));
        }

        $this->line('=== SADECE N11 ===');
        foreach ($n11Only as $productId) {
            $listing = $n11->get($productId);
            $product = $listing?->product;
            $this->line($this->formatRow('N11_ONLY', $productId, $product, $listing));
        }

        $this->line('=== İKİSİNDE DE AMA DURUM FARKLI ===');
        foreach ($both as $productId) {
            $a = $ty->get($productId);
            $b = $n11->get($productId);
            if ($a === null || $b === null || $a->status === $b->status) {
                continue;
            }
            $product = $a->product ?? Product::query()->find($productId);
            $this->line(sprintf(
                'DIFF | #%s | %s | ty=%s n11=%s | ty_ext=%s | n11_ext=%s',
                $productId,
                (string) ($product?->sku ?: '-'),
                (string) $a->status,
                (string) $b->status,
                (string) ($a->external_product_id ?: '-'),
                (string) ($b->external_product_id ?: '-')
            ));
        }

        $this->line('=== N11 PENDING LİSTESİ ===');
        foreach ($n11->where('status', 'pending') as $listing) {
            $product = $listing->product;
            $this->line($this->formatRow('N11_PENDING', (int) $listing->product_id, $product, $listing));
        }

        if ($this->option('inspect-pending')) {
            $channel = MarketplaceChannel::query()->where('key', 'n11')->first();
            if ($channel === null) {
                $this->warn('N11 kanalı yok.');

                return self::SUCCESS;
            }

            try {
                $client = $n11Api->forChannel($channel);
            } catch (Throwable $e) {
                $this->warn('N11 API: '.$e->getMessage());

                return self::SUCCESS;
            }

            foreach ($n11->where('status', 'pending') as $listing) {
                $taskId = (int) ($listing->external_product_id ?: 0);
                if ($taskId <= 0) {
                    continue;
                }
                $this->info('=== N11 TASK '.$taskId.' (sku='.(string) ($listing->product?->sku ?: '-').') ===');
                try {
                    $details = $client->taskDetails($taskId);
                    $content = data_get($details, 'skus.content.0') ?? data_get($details, 'content.0');
                    $this->line(sprintf(
                        'taskStatus=%s itemStatus=%s reasons=%s stockCode=%s n11ProductId=%s',
                        (string) data_get($details, 'status', '-'),
                        (string) data_get($content, 'status', '-'),
                        json_encode(data_get($content, 'sku.reasons') ?? data_get($content, 'reasons') ?? [], JSON_UNESCAPED_UNICODE),
                        (string) data_get($content, 'sku.stockCode', data_get($content, 'itemCode', '-')),
                        (string) data_get($content, 'sku.n11ProductId', '-')
                    ));
                } catch (Throwable $e) {
                    $this->error('Task '.$taskId.': '.$e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }

    private function formatRow(string $tag, int $productId, ?Product $product, ?MarketplaceListing $listing): string
    {
        return sprintf(
            '%s | #%s | %s | %s | brand=%s | status=%s | price=%s | ext=%s | err=%s',
            $tag,
            $productId,
            (string) ($product?->sku ?: '-'),
            (string) ($product?->slug ?: '-'),
            (string) ($product?->brand?->name ?: '-'),
            (string) ($listing?->status ?: '-'),
            (string) ($listing?->channel_price ?: '-'),
            (string) ($listing?->external_product_id ?: '-'),
            mb_substr((string) ($listing?->last_error ?: ''), 0, 100)
        );
    }
}
