<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Pricing\DataForSeoClient;
use App\Services\Pricing\GoogleShoppingMarketScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanGoogleMarketPriceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    /** @var list<int> */
    public array $backoff = [60, 180];

    public function __construct(public int $productId) {}

    public function uniqueId(): string
    {
        return 'google-market-scan-'.$this->productId;
    }

    public function handle(GoogleShoppingMarketScanner $scanner, DataForSeoClient $client): void
    {
        if (! $client->configured()) {
            return;
        }

        $product = Product::query()->find($this->productId);
        if (! $product || ! $product->is_active) {
            return;
        }

        $scanner->scanProduct($product);
    }
}
