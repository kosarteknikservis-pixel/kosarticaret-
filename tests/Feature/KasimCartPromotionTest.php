<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use App\Services\CartPricingService;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasimCartPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_highest_matching_fixed_tier_applies(): void
    {
        foreach (PromotionService::kasim2026Tiers() as $index => $tier) {
            Promotion::query()->create([
                'name' => PromotionService::KASIM_2026_NAME_PREFIX.$tier['label'],
                'type' => Promotion::TYPE_FIXED,
                'value' => $tier['discount'],
                'min_cart' => $tier['min'],
                'auto_apply' => true,
                'active' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => PromotionService::kasim2026EndsAt(),
                'priority' => 100 + $index,
            ]);
        }

        $promo = app(PromotionService::class);

        $this->assertSame(0.0, $promo->autoBenefits(999, [])['discount']);
        $this->assertSame(150.0, $promo->autoBenefits(1000, [])['discount']);
        $this->assertSame(300.0, $promo->autoBenefits(3500, [])['discount']);
        $this->assertSame(500.0, $promo->autoBenefits(6000, [])['discount']);
        $this->assertSame(800.0, $promo->autoBenefits(10000, [])['discount']);
        $this->assertSame(1100.0, $promo->autoBenefits(12500, [])['discount']);
    }

    public function test_cart_pricing_exposes_kasim_discount(): void
    {
        Promotion::query()->create([
            'name' => PromotionService::KASIM_2026_NAME_PREFIX.'1.000 TL üzeri 150 TL',
            'type' => Promotion::TYPE_FIXED,
            'value' => 150,
            'min_cart' => 1000,
            'auto_apply' => true,
            'active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => PromotionService::kasim2026EndsAt(),
            'priority' => 100,
        ]);

        $product = Product::query()->create([
            'slug' => 'kasim-promo-test',
            'sku' => 'KASIM-PROMO-1',
            'name' => 'Kasım Promo Test',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->withSession(['cart' => [$product->id => 1]]);

        $breakdown = app(CartPricingService::class)->breakdown();

        $this->assertEqualsWithDelta(1200.0, $breakdown['subtotal'], 0.01);
        $this->assertEqualsWithDelta(150.0, $breakdown['promotion_discount'], 0.01);
        $this->assertNotNull($breakdown['promotion_label']);
    }
}
