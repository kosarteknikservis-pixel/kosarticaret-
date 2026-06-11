<?php

namespace Tests\Feature;

use App\Services\CheckoutCalculator;
use App\Services\StoreConfig;
use Mockery;
use Tests\TestCase;

class CheckoutCalculatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_shipping_is_free_when_subtotal_meets_threshold(): void
    {
        $store = Mockery::mock(StoreConfig::class);
        $store->shouldReceive('shippingRates')->andReturn(['cargo_1' => 300.0]);
        $store->shouldReceive('freeShippingMin')->andReturn(1000.0);

        $calculator = new CheckoutCalculator($store);

        $this->assertSame(0.0, $calculator->shippingCost(7852.0, 'cargo_1'));
        $this->assertSame(300.0, $calculator->shippingCost(850.0, 'cargo_1'));
    }

    public function test_express_shipping_stays_paid_above_threshold(): void
    {
        $store = Mockery::mock(StoreConfig::class);
        $store->shouldReceive('shippingRates')->andReturn(['hizli' => 149.9]);
        $store->shouldReceive('freeShippingMin')->andReturn(1000.0);

        $calculator = new CheckoutCalculator($store);

        $this->assertSame(149.9, $calculator->shippingCost(7852.0, 'hizli'));
    }

    public function test_promotion_can_force_free_shipping_below_threshold(): void
    {
        $store = Mockery::mock(StoreConfig::class);
        $store->shouldReceive('shippingRates')->andReturn(['cargo_1' => 300.0]);
        $store->shouldReceive('freeShippingMin')->andReturn(1000.0);

        $calculator = new CheckoutCalculator($store);

        $this->assertSame(0.0, $calculator->shippingCost(500.0, 'cargo_1', true));
    }
}
