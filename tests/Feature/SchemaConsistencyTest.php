<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_return_days_defaults_to_fourteen_when_unset(): void
    {
        $this->assertSame(14, Seo::merchantReturnDays());
        $this->assertSame(14, Seo::merchantReturnPolicy()['merchantReturnDays']);
    }

    public function test_merchant_return_days_uses_panel_value_when_higher_than_fourteen(): void
    {
        SiteSetting::set('return_days', '30');

        $this->assertSame(30, Seo::merchantReturnDays());
        $this->assertSame(30, Seo::merchantReturnPolicy()['merchantReturnDays']);
    }

    public function test_merchant_return_days_never_goes_below_fourteen(): void
    {
        SiteSetting::set('return_days', '7');

        $this->assertSame(14, Seo::merchantReturnDays());
    }

    public function test_offer_shipping_handling_and_transit_max_use_panel_or_defaults(): void
    {
        $defaults = Seo::offerShippingDetails();
        $this->assertSame(2, $defaults['deliveryTime']['handlingTime']['maxValue']);
        $this->assertSame(3, $defaults['deliveryTime']['transitTime']['maxValue']);
        $this->assertSame(1, $defaults['deliveryTime']['handlingTime']['minValue']);
        $this->assertSame(1, $defaults['deliveryTime']['transitTime']['minValue']);

        SiteSetting::set('shipping_handling_max_days', '5');
        SiteSetting::set('shipping_transit_max_days', '7');

        $custom = Seo::offerShippingDetails();
        $this->assertSame(5, $custom['deliveryTime']['handlingTime']['maxValue']);
        $this->assertSame(7, $custom['deliveryTime']['transitTime']['maxValue']);
    }
}
