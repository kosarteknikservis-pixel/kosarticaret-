<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartUnavailableProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    public function test_inactive_product_cannot_be_added_via_ajax(): void
    {
        $product = Product::query()->create([
            'slug' => 'pasif-sepet-test',
            'sku' => 'PASIF-1',
            'name' => 'Pasif Sepet Test',
            'price' => 250,
            'stock' => 5,
            'is_active' => false,
        ]);

        $this->postJson('/sepet/ajax/ekle/'.$product->slug, ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('count', 0)
            ->assertJsonFragment(['message' => 'Bu ürün şu an satışta değil.']);

        $this->assertSame([], session('cart', []));
    }

    public function test_zero_price_product_cannot_be_added_via_ajax(): void
    {
        $product = Product::query()->create([
            'slug' => 'sifir-fiyat-sepet-test',
            'sku' => 'SIFIR-1',
            'name' => 'Sıfır Fiyat Sepet Test',
            'price' => 0,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->postJson('/sepet/ajax/ekle/'.$product->slug, ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('count', 0)
            ->assertJsonFragment(['message' => 'Bu ürün şu an satışta değil.']);

        $this->assertSame([], session('cart', []));
    }

    public function test_checkout_rejects_cart_when_product_becomes_inactive(): void
    {
        $product = Product::query()->create([
            'slug' => 'checkout-pasif-test',
            'sku' => 'CHK-PASIF-1',
            'name' => 'Checkout Pasif Test',
            'price' => 1500,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();
        $this->assertSame(1, (int) (session('cart')[$product->id] ?? 0));

        $product->update(['is_active' => false]);

        $ordersBefore = Order::query()->count();

        $this->from(route('checkout.show'))
            ->post('/odeme', [
                'ad' => 'Ahmet',
                'soyad' => 'Yılmaz',
                'eposta' => 'ahmet@example.com',
                'telefon' => '0532 111 22 33',
                'il' => 'İSTANBUL',
                'ilce' => 'KADIKÖY',
                'adres' => 'Caferağa Mah. Moda Cad. No:5 D:3',
                'kargo_yontemi' => 'standart',
                'odeme_yontemi' => 'havale',
                'sozlesme' => '1',
            ])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasErrors('cart');

        $this->assertSame($ordersBefore, Order::query()->count());
    }
}
