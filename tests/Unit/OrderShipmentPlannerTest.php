<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Shipping\OrderShipmentPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShipmentPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_splits_quantity_by_units_per_carton(): void
    {
        $product = Product::query()->create([
            'name' => 'Koli Ürün',
            'slug' => 'koli-urun',
            'sku' => 'KOLI-001',
            'price' => 500,
            'stock' => 20,
            'units_per_carton' => 3,
            'weight_kg' => 2,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'order_number' => 'KOS-PLAN01',
            'email' => 'test@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kredi_karti',
            'subtotal' => 500,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 500,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 7,
            'unit_price' => 500,
            'line_total' => 500,
        ]);

        $packages = app(OrderShipmentPlanner::class)->suggest($order->fresh('items.product'));

        $this->assertCount(3, $packages);
        $this->assertSame([3, 3, 1], array_map(
            fn (array $package) => $package['items'][0]['quantity'],
            $packages
        ));
    }

    public function test_keeps_single_package_when_no_carton_size(): void
    {
        $order = Order::query()->create([
            'order_number' => 'KOS-PLAN02',
            'email' => 'test@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kredi_karti',
            'subtotal' => 100,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Fan Motoru',
            'quantity' => 5,
            'unit_price' => 20,
            'line_total' => 100,
        ]);

        $packages = app(OrderShipmentPlanner::class)->suggest($order->fresh('items.product'));

        $this->assertCount(1, $packages);
        $this->assertSame(5, $packages[0]['items'][0]['quantity']);
    }
}
