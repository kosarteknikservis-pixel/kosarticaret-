<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderStockDecrementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
        Queue::fake();
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(array $extra = []): array
    {
        return array_merge([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'eposta' => 'stok-test@example.com',
            'telefon' => '0532 111 22 33',
            'il' => 'İSTANBUL',
            'ilce' => 'KADIKÖY',
            'adres' => 'Caferağa Mah. Moda Cad. No:5 D:3',
            'kargo_yontemi' => 'standart',
            'odeme_yontemi' => 'havale',
            'sozlesme' => '1',
        ], $extra);
    }

    private function seedSellableProduct(int $stock): Product
    {
        return Product::query()->create([
            'slug' => 'stok-test-pompa',
            'name' => 'Stok Test Pompa',
            'sku' => 'STOK-TEST-1',
            'price' => 1500,
            'stock' => $stock,
            'is_active' => true,
            'description' => 'Stok düşümü test ürünü',
        ]);
    }

    #[Test]
    public function successful_order_decrements_stock_atomically(): void
    {
        $product = $this->seedSellableProduct(3);

        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect();

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $this->assertSame(1, (int) $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function second_session_order_fails_without_negative_stock(): void
    {
        $product = $this->seedSellableProduct(1);
        $payload = $this->checkoutPayload();

        $this->withSession(['cart' => [$product->id => 1]])
            ->from(route('checkout.show'))
            ->post(route('checkout.store'), $payload)
            ->assertRedirect();

        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());

        $this->flushSession();

        $this->withSession(['cart' => [$product->id => 1]])
            ->from(route('checkout.show'))
            ->post(route('checkout.store'), array_merge($payload, [
                'eposta' => 'stok-test-2@example.com',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame(1, Order::query()->count());
    }
}
