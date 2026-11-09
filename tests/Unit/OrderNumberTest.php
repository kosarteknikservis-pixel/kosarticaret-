<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\CheckoutCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OrderNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Str::createRandomStringsNormally();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_order_number_matches_prefix_timestamp_and_suffix_pattern(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        Str::createRandomStringsUsing(fn () => 'abcd');

        $number = app(CheckoutCalculator::class)->orderNumber();

        $this->assertMatchesRegularExpression('/^KOS-[0-9A-Z]{10}$/', $number);
    }

    public function test_order_number_retries_when_collision_exists(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        $zaman = strtoupper(base_convert((string) now()->timestamp, 10, 36));

        Str::createRandomStringsUsingSequence(['aaaa', 'bbbb']);

        Order::query()->create([
            'order_number' => 'KOS-'.$zaman.'AAAA',
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
        ]);

        $number = app(CheckoutCalculator::class)->orderNumber();

        $this->assertSame('KOS-'.$zaman.'BBBB', $number);
    }

    public function test_order_number_throws_after_repeated_collisions(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        $zaman = strtoupper(base_convert((string) now()->timestamp, 10, 36));

        Str::createRandomStringsUsing(fn () => 'aaaa');

        Order::query()->create([
            'order_number' => 'KOS-'.$zaman.'AAAA',
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
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sipariş numarası üretilemedi.');

        app(CheckoutCalculator::class)->orderNumber();
    }
}
