<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\PaymentGatewayConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductInstallmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function product_page_shows_installments_tab(): void
    {
        $product = Product::query()->where('is_active', true)->first();
        $this->assertNotNull($product);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(__('shop.tab_installments'), false)
            ->assertSee('shop-pdp-installments', false);
    }

    #[Test]
    public function product_page_uses_paytr_installment_table_token_when_available(): void
    {
        SiteSetting::set('payment_gateway', 'paytr');
        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');
        SiteSetting::set('paytr_installment_table_token', 'table-token');

        $product = Product::query()->where('is_active', true)->first();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-installments-mode="paytr-table"', false)
            ->assertSee('data-paytr-table-token="table-token"', false)
            ->assertSee('id="paytr_taksit_tablosu"', false);
    }

    #[Test]
    public function installment_api_returns_unavailable_when_mock_gateway(): void
    {
        SiteSetting::set('payment_gateway', 'mock');

        $product = Product::query()->where('is_active', true)->first();

        $this->getJson(route('products.installments', $product))
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonStructure(['message', 'html']);
    }

    #[Test]
    public function installment_api_uses_iyzico_when_configured(): void
    {
        SiteSetting::set('payment_gateway', 'iyzico');
        SiteSetting::set('iyzico_api_key', 'test-api');
        SiteSetting::set('iyzico_secret_key', 'test-secret');
        SiteSetting::set('iyzico_sandbox', '1');

        Http::fake([
            'sandbox-api.iyzipay.com/payment/iyzipos/installment' => Http::response([
                'status' => 'success',
                'installmentDetails' => [
                    [
                        'cardType' => 'CREDIT_CARD',
                        'cardFamilyName' => 'Bonus',
                        'installmentPrices' => [
                            ['installmentNumber' => 1, 'installmentPrice' => 1000.0, 'totalPrice' => 1000.0],
                            ['installmentNumber' => 3, 'installmentPrice' => 340.0, 'totalPrice' => 1020.0],
                        ],
                    ],
                ],
            ]),
        ]);

        $product = Product::query()->where('is_active', true)->first();

        $this->getJson(route('products.installments', ['product' => $product, 'amount' => 1000]))
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('provider', 'iyzico')
            ->assertJsonFragment(['label' => 'Bonus']);
    }

    #[Test]
    public function installment_api_uses_paytr_when_configured(): void
    {
        SiteSetting::set('payment_gateway', 'paytr');
        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');

        Http::fake([
            'www.paytr.com/odeme/taksit-oranlari' => Http::response([
                'status' => 'success',
                'max_inst_non_bus' => 6,
                'rates' => [
                    'bonus' => [
                        'taksit_2' => 2.5,
                        'taksit_3' => 3.0,
                    ],
                ],
            ]),
        ]);

        $product = Product::query()->where('is_active', true)->first();

        $response = $this->getJson(route('products.installments', ['product' => $product, 'amount' => 1000]));
        $response->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('provider', 'paytr');

        $rows = $response->json('rows');
        $this->assertNotEmpty($rows);
        $this->assertSame('Bonus', $rows[0]['label']);
        $this->assertTrue(PaymentGatewayConfig::isLive());
    }

    #[Test]
    public function installment_api_accepts_paytr_turkish_rates_payload(): void
    {
        SiteSetting::set('payment_gateway', 'paytr');
        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');

        Http::fake([
            'www.paytr.com/odeme/taksit-oranlari' => Http::response([
                'status' => 'Success',
                'max_inst_non_bus' => 6,
                'oranlar' => [
                    'world' => [
                        'taksit_2' => 2.4,
                        'taksit_6' => 5.2,
                    ],
                ],
            ]),
        ]);

        $product = Product::query()->where('is_active', true)->first();

        $response = $this->getJson(route('products.installments', ['product' => $product, 'amount' => 1000]));
        $response->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('provider', 'paytr');

        $rows = $response->json('rows');
        $this->assertNotEmpty($rows);
        $this->assertSame('World', $rows[0]['label']);
    }

    #[Test]
    public function installment_api_accepts_nested_paytr_rate_rows(): void
    {
        SiteSetting::set('payment_gateway', 'paytr');
        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');

        Http::fake([
            'www.paytr.com/odeme/taksit-oranlari' => Http::response([
                'status' => 'success',
                'max_inst_non_bus' => 6,
                'oranlar' => [
                    'bonus' => [
                        '2' => ['oran' => '2,5'],
                        ['taksit' => 3, 'oran' => '3.1'],
                    ],
                    [
                        'card_type' => 'world',
                        'installment_number' => 6,
                        'rate' => 5.2,
                    ],
                ],
            ]),
        ]);

        $product = Product::query()->where('is_active', true)->first();

        $response = $this->getJson(route('products.installments', ['product' => $product, 'amount' => 1000]));
        $response->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('provider', 'paytr');

        $rows = $response->json('rows');
        $this->assertCount(2, $rows);
    }

    #[Test]
    public function installment_api_does_not_cache_empty_paytr_rates(): void
    {
        SiteSetting::set('payment_gateway', 'paytr');
        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');

        Http::fake([
            'www.paytr.com/odeme/taksit-oranlari' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'max_inst_non_bus' => 6,
                    'oranlar' => [],
                ])
                ->push([
                    'status' => 'success',
                    'max_inst_non_bus' => 6,
                    'oranlar' => [
                        'world' => [
                            'taksit_2' => 2.4,
                        ],
                    ],
                ]),
        ]);

        $product = Product::query()->where('is_active', true)->first();

        $this->getJson(route('products.installments', ['product' => $product, 'amount' => 1000]))
            ->assertOk()
            ->assertJsonPath('available', false);

        $this->getJson(route('products.installments', ['product' => $product, 'amount' => 1000]))
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('provider', 'paytr');
    }
}
