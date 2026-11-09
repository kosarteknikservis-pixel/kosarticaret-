<?php

namespace Tests\Feature;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\SiteSetting;
use App\Services\Shipping\ShipmentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShipmentNotificationMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_after_shipment_submitted_skips_mail_when_smtp_is_off(): void
    {
        Mail::fake();

        SiteSetting::set('smtp_enabled', '0');
        Cache::flush();

        $order = $this->makeOrder([
            'phone' => null,
            'email' => 'kargo@example.com',
        ]);

        $shipment = new OrderShipment;
        $shipment->tracking_number = 'TEST123';

        app(ShipmentNotificationService::class)->afterShipmentSubmitted($order, $shipment);

        Mail::assertNothingSent();
    }

    public function test_after_shipment_submitted_sends_status_mail_when_smtp_is_configured(): void
    {
        Mail::fake();

        SiteSetting::set('smtp_enabled', '1');
        SiteSetting::set('smtp_host', 'smtp.test');
        SiteSetting::set('smtp_port', '587');
        SiteSetting::set('smtp_from_address', 'info@test.com');
        Cache::flush();

        $order = $this->makeOrder([
            'phone' => null,
            'email' => 'kargo@example.com',
        ]);

        $shipment = new OrderShipment;
        $shipment->tracking_number = 'TEST123';

        app(ShipmentNotificationService::class)->afterShipmentSubmitted($order, $shipment);

        Mail::assertSent(OrderStatusUpdatedMail::class, function (OrderStatusUpdatedMail $mail) use ($order): bool {
            return $mail->hasTo($order->email);
        });
    }

    /** @param  array<string, mixed>  $overrides */
    private function makeOrder(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'order_number' => 'KOS-SHIP-MAIL-1',
            'email' => 'pending@example.com',
            'status' => 'odeme_bekliyor',
            'payment_status' => 'bekliyor',
            'payment_method' => 'kredi_karti',
            'customer_name' => 'Test User',
            'phone' => '5550000000',
            'shipping_address' => ['teslimat' => ['ad' => 'Test', 'soyad' => 'User']],
            'subtotal' => 5990,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 5990,
            'sales_channel' => 'website',
        ], $overrides));
    }
}
