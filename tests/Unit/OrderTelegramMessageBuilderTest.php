<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Telegram\OrderTelegramMessageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTelegramMessageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_single_product_message_with_corporate_invoice(): void
    {
        $order = Order::query()->create([
            'order_number' => 'KOS-1001',
            'email' => 'test@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kapida_odeme',
            'customer_name' => 'Savaş Yılmaztürk',
            'phone' => '05333450678',
            'shipping_address' => [
                'teslimat' => [
                    'il' => 'Giresun',
                    'ilce' => 'Bulancak',
                    'adres' => '2. Organize mevki 5. cadde no 23',
                    'kurumsalFatura' => [
                        'firmaAdi' => 'Milenyum conveyör',
                        'vergiNumarasi' => '6211055100',
                        'vergiDairesi' => 'Bulancak',
                        'faturaAdresi' => '2. Organize mevki 5. cadde no 23',
                    ],
                ],
            ],
            'subtotal' => 6399,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 6399,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'GRW-D750 Sanayi Tipi Vantilatör (Duvar Modeli)',
            'quantity' => 1,
            'unit_price' => 6399,
            'line_total' => 6399,
        ]);

        $message = app(OrderTelegramMessageBuilder::class)->build($order);

        $this->assertStringContainsString('Yeni Sipariş ✅', $message);
        $this->assertStringContainsString('#KOS-1001', $message);
        $this->assertStringContainsString('Savaş Yılmaztürk', $message);
        $this->assertStringContainsString('Ürün: GRW-D750 Sanayi Tipi Vantilatör (Duvar Modeli)', $message);
        $this->assertStringContainsString('6399.00 ₺', $message);
        $this->assertStringContainsString('Giresun / Bulancak', $message);
        $this->assertStringContainsString('--- Kurumsal fatura ---', $message);
        $this->assertStringContainsString('🔗 Panel:', $message);
        $this->assertStringContainsString('🔗 Site:', $message);
    }

    public function test_builds_marketplace_message_with_channel_label(): void
    {
        $order = Order::query()->create([
            'order_number' => 'TY-12345',
            'email' => 'trendyol@marketplace.local',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'pazaryeri',
            'customer_name' => 'Trendyol Müşteri',
            'phone' => '05551234567',
            'shipping_address' => [
                'kaynak' => 'trendyol',
                'shipment' => [
                    'city' => 'İstanbul',
                    'district' => 'Kadıköy',
                    'fullAddress' => 'Moda Cd. No:1',
                ],
            ],
            'subtotal' => 100,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 100,
            'sales_channel' => 'trendyol',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Test ürün',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $message = app(OrderTelegramMessageBuilder::class)->build($order);

        $this->assertStringContainsString('Kanal: Trendyol', $message);
        $this->assertStringContainsString('İstanbul / Kadıköy', $message);
        $this->assertStringContainsString('Moda Cd. No:1', $message);
    }
}
