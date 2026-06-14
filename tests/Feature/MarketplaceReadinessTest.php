<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Marketplace\ProductReadinessChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_without_barcode_is_not_ready(): void
    {
        $this->seed();

        $product = Product::query()->firstOrFail();
        $product->update([
            'barcode' => null,
            'marketplace_enabled' => true,
            'is_active' => true,
        ]);

        $result = app(ProductReadinessChecker::class)->evaluate($product->fresh(['brand', 'categories', 'images']));

        $this->assertFalse($result['ready']);
        $this->assertLessThan(100, $result['score']);
    }

    public function test_marketplace_channels_are_seeded(): void
    {
        $this->seed();

        $this->assertDatabaseCount('marketplace_channels', 6);
        $this->assertDatabaseHas('marketplace_channels', ['key' => 'trendyol']);
    }
}
