<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Pricing\DataForSeoClient;
use App\Services\Pricing\GoogleShoppingMarketScanner;
use Illuminate\Console\Command;

class ScanGoogleMarketPricesCommand extends Command
{
    protected $signature = 'pricing:scan-google
                            {--product= : Tek ürün ID}
                            {--limit=20 : En fazla kaç ürün taransın}
                            {--only-missing : Daha önce taranmamış ürünler}
                            {--stale-days=7 : Bu günden eski taramaları yenile (only-missing yoksa)}';

    protected $description = 'Aktif ürünleri Google Shopping’da tarar; fiyatı otomatik uygulamaz.';

    public function handle(GoogleShoppingMarketScanner $scanner, DataForSeoClient $client): int
    {
        if (! $client->configured()) {
            $this->error('DATAFORSEO_USERNAME / DATAFORSEO_PASSWORD tanımlı değil.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $query = Product::query()->where('is_active', true)->orderBy('id');

        if ($this->option('product')) {
            $query->whereKey((int) $this->option('product'));
        } elseif ($this->option('only-missing')) {
            $query->whereDoesntHave('marketPriceScan');
        } else {
            $days = max(1, (int) $this->option('stale-days'));
            $query->where(function ($q) use ($days) {
                $q->whereDoesntHave('marketPriceScan')
                    ->orWhereHas('marketPriceScan', function ($scan) use ($days) {
                        $scan->where(function ($inner) use ($days) {
                            $inner->whereNull('last_scanned_at')
                                ->orWhere('last_scanned_at', '<', now()->subDays($days));
                        });
                    });
            });
        }

        $products = $query->limit($limit)->get();
        if ($products->isEmpty()) {
            $this->warn('Taranacak ürün yok.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($products as $product) {
            $this->line("→ #{$product->id} {$product->name}");
            $scan = $scanner->scanProduct($product);

            if ($scan->status === 'error' || $scan->last_error) {
                if ($scan->status === 'error') {
                    $fail++;
                    $this->error('  ✗ '.$scan->last_error);
                    continue;
                }
            }

            $ok++;
            $min = $scan->google_min_price !== null
                ? number_format((float) $scan->google_min_price, 2, ',', '.').' ₺'
                : '—';
            $this->info("  ✓ {$scan->status} · {$scan->offer_count} teklif · min {$min}");
        }

        $this->info("Tamam: {$ok} tarama, {$fail} hata.");

        return self::SUCCESS;
    }
}
