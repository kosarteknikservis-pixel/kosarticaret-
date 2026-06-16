<?php

namespace Tests\Unit;

use App\Support\Code128Barcode;
use App\Support\OrderShippingLabel;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShippingLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_code128_generates_svg_for_order_number(): void
    {
        $svg = Code128Barcode::svg('KOS-T6Q22A');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<rect', $svg);
        $this->assertStringContainsString('KOS-T6Q22A', $svg);
    }

    public function test_label_builds_recipient_and_product_summary(): void
    {
        $order = Order::query()->create([
            'order_number' => 'KOS-TEST01',
            'email' => 'musteri@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kredi_karti',
            'customer_name' => 'Mukdat Okur',
            'phone' => '05321234567',
            'shipping_address' => [
                'teslimat' => [
                    'ad' => 'Mukdat',
                    'soyad' => 'Okur',
                    'telefon' => '05321234567',
                    'il' => 'Bursa',
                    'ilce' => 'Nilüfer',
                    'adres' => 'Organize Sanayi Bölgesi No: 12',
                ],
                'kargo_firma' => ['name' => 'Aras Kargo'],
            ],
            'subtotal' => 100,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Dalgıç Pompa 1 HP',
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
        ]);

        $label = OrderShippingLabel::for($order->fresh('items'));

        $this->assertSame('Mukdat Okur', $label->recipientName());
        $this->assertSame('05321234567', $label->recipientPhone());
        $this->assertSame('Aras Kargo', $label->cargoCompany());
        $this->assertStringContainsString('2×', $label->productSummary());
        $this->assertSame('KOS-TEST01', $label->barcodeValue());
        $this->assertStringContainsString('Kredi kartı', $label->orderPaymentSummary());
        $this->assertStringContainsString('Gönderici ödemeli', $label->shippingPaymentSummary());
    }

    public function test_cod_label_shows_collection_and_sender_paid_shipping(): void
    {
        $order = Order::query()->create([
            'order_number' => 'KOS-COD01',
            'email' => 'musteri@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kapida_odeme',
            'customer_name' => 'Ali Veli',
            'phone' => '05321234567',
            'shipping_address' => [
                'teslimat' => [
                    'ad' => 'Ali',
                    'soyad' => 'Veli',
                    'il' => 'Bursa',
                    'ilce' => 'Osmangazi',
                    'adres' => 'Test adres',
                ],
                'kargo_firma' => ['name' => 'MNG Kargo'],
            ],
            'subtotal' => 500,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 529.90,
        ]);

        $label = OrderShippingLabel::for($order);

        $this->assertStringContainsString('529,90 ₺ tahsil', $label->orderPaymentSummary());
        $this->assertStringContainsString('Gönderici ödemeli', $label->shippingPaymentSummary());
        $this->assertTrue($label->isCashOnDelivery());
    }
}
