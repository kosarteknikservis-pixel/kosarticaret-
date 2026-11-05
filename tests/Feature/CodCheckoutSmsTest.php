<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Checkout\CodSmsVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CodCheckoutSmsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function checkoutPayload(string $payment = 'kapida_odeme', array $extra = []): array
    {
        return array_merge([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'eposta' => 'ahmet@example.com',
            'telefon' => '0532 111 22 33',
            'il' => 'İSTANBUL',
            'ilce' => 'KADIKÖY',
            'adres' => 'Caferağa Mah. Moda Cad. No:5 D:3',
            'kargo_yontemi' => 'standart',
            'odeme_yontemi' => $payment,
            'sozlesme' => '1',
        ], $extra);
    }

    private function enableCodSms(): void
    {
        foreach ([
            'cod_sms_verify_enabled' => '1',
            'mutlucell_username' => 'kosar',
            'mutlucell_password' => 'api-key',
            'mutlucell_header' => 'KOSAR',
            'payment_checkout_enabled' => 'kredi_karti,havale,kapida_odeme',
            'cod_fee' => '29.90',
        ] as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::flush();
    }

    private function addProductToCart(): Product
    {
        $product = Product::query()->create([
            'slug' => 'test-cod-hidrofor',
            'name' => 'Test COD Hidrofor',
            'price' => 1500,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();

        return $product;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    #[Test]
    public function kapida_odeme_requires_sms_before_order_when_enabled(): void
    {
        $this->enableCodSms();
        $this->addProductToCart();

        Http::fake(['smsgw.mutlucell.com/*' => Http::response('1234567', 200)]);

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('checkout.show'));

        $this->assertSame(0, Order::query()->count());
        $this->assertTrue(session()->has(CodSmsVerification::SESSION_KEY));

        $sms = collect(Http::recorded())->first(fn (array $pair) => str_contains($pair[0]->url(), 'mutlucell'))[0];
        preg_match('/kodunuz: (\d{6})/', (string) $sms->body(), $match);
        $code = $match[1];

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), $this->checkoutPayload(extra: ['otp_code' => $code]))
            ->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame('kapida_odeme', $order->payment_method);
        $this->assertSame('hazirlaniyor', $order->status);
        $this->assertSame('basarili', $order->payment_status);
        $this->assertFalse(session()->has(CodSmsVerification::SESSION_KEY));
    }

    #[Test]
    public function havale_skips_sms_even_when_cod_sms_enabled(): void
    {
        $this->enableCodSms();
        $this->addProductToCart();

        Http::fake();

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), $this->checkoutPayload('havale'))
            ->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame('havale', $order->payment_method);
        Http::assertNothingSent();
    }

    #[Test]
    public function kapida_odeme_skips_sms_when_panel_toggle_is_off(): void
    {
        SiteSetting::query()->updateOrCreate(['key' => 'cod_sms_verify_enabled'], ['value' => '0']);
        SiteSetting::query()->updateOrCreate(['key' => 'payment_checkout_enabled'], ['value' => 'kredi_karti,havale,kapida_odeme']);
        Cache::flush();

        $this->addProductToCart();
        Http::fake();

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $this->assertSame(1, Order::query()->count());
        Http::assertNothingSent();
    }
}
