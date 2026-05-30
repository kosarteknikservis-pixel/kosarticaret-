<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\PaymentGatewayConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function payment_gateway_reads_from_site_settings(): void
    {
        SiteSetting::set('payment_gateway', 'paytr');
        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');

        $this->assertSame('paytr', PaymentGatewayConfig::activeProvider());
        $this->assertTrue(PaymentGatewayConfig::isConfigured('paytr'));
        $this->assertTrue(PaymentGatewayConfig::isLive());
    }

    #[Test]
    public function admin_can_save_iyzico_settings_on_separate_page(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->put(route('admin.integrations.payment.iyzico.update'), [
                'iyzico_api_key' => 'sandbox-key',
                'iyzico_secret_key' => 'sandbox-secret',
                'iyzico_sandbox' => '1',
                'set_active' => '1',
            ])
            ->assertRedirect(route('admin.integrations.payment.iyzico'));

        $this->assertSame('iyzico', PaymentGatewayConfig::activeProvider());
        $this->assertSame('sandbox-key', PaymentGatewayConfig::iyzicoApiKey());
    }

    #[Test]
    public function admin_payment_index_lists_providers(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.integrations.payment.index'))
            ->assertOk()
            ->assertSee('Ödeme', false)
            ->assertSee('PayTR', false)
            ->assertSee('iyzico', false);
    }

    #[Test]
    public function admin_paytr_and_iyzico_pages_load_separately(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.integrations.payment.paytr'))
            ->assertOk()
            ->assertSee('Entegrasyonlar', false)
            ->assertSee('PayTR', false);

        $this->actingAs($admin)
            ->get(route('admin.integrations.payment.iyzico'))
            ->assertOk()
            ->assertSee('iyzico', false);
    }
}
