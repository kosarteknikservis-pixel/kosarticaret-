<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentPendingOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        SiteSetting::set('paytr_merchant_id', '12345');
        SiteSetting::set('paytr_merchant_key', 'test-key');
        SiteSetting::set('paytr_merchant_salt', 'test-salt');
    }

    #[Test]
    public function paytr_failed_callback_marks_order_as_failed(): void
    {
        $order = $this->makePendingOrder();

        $merchantOid = 'KOS-TEST001';
        $status = 'failed';
        $totalAmount = '599000';

        $this->post(route('payment.paytr.callback'), [
            'merchant_oid' => $merchantOid,
            'status' => $status,
            'total_amount' => $totalAmount,
            'hash' => $this->paytrHash($merchantOid, $status, $totalAmount),
            'failed_reason_code' => '011',
            'failed_reason_msg' => 'Yetersiz bakiye',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('basarisiz', $order->payment_status);
        $this->assertSame('odeme_bekliyor', $order->status);
        $this->assertNotNull($order->payment_failed_at);
        $this->assertTrue($order->logs()->where('type', 'payment_failed')->exists());
    }

    #[Test]
    public function paytr_success_callback_confirms_payment(): void
    {
        $order = $this->makePendingOrder();

        $merchantOid = 'KOSTEST001';
        $status = 'success';
        $totalAmount = '599000';

        $this->post(route('payment.paytr.callback'), [
            'merchant_oid' => $merchantOid,
            'status' => $status,
            'total_amount' => $totalAmount,
            'hash' => $this->paytrHash($merchantOid, $status, $totalAmount),
        ])->assertOk();

        $order->refresh();
        $this->assertSame('basarili', $order->payment_status);
        $this->assertSame('hazirlaniyor', $order->status);
    }

    #[Test]
    public function payment_page_with_error_query_marks_order_failed(): void
    {
        $order = $this->makePendingOrder();

        $this->get(route('checkout.payment', ['order' => $order->order_number, 'durum' => 'hata']))
            ->assertOk()
            ->assertSee(__('shop.payment_failed'), false);

        $order->refresh();
        $this->assertSame('basarisiz', $order->payment_status);
    }

    #[Test]
    public function payment_reminder_command_sends_email_for_eligible_orders(): void
    {
        Mail::fake();

        config(['kosar.payment_reminder.delay_hours' => 2]);

        $eligible = $this->makePendingOrder([
            'created_at' => now()->subHours(3),
        ]);

        $this->makePendingOrder([
            'order_number' => 'KOS-RECENT',
            'created_at' => now()->subMinutes(30),
        ]);

        $this->artisan('orders:send-payment-reminders')
            ->assertSuccessful();

        $eligible->refresh();
        $this->assertNotNull($eligible->payment_reminder_sent_at);
        Mail::assertSent(\App\Mail\OrderPaymentReminderMail::class, 1);
    }

    #[Test]
    public function admin_payment_reminder_route_sends_mail_for_pending_order(): void
    {
        Mail::fake();

        SiteSetting::set('smtp_enabled', '1');
        SiteSetting::set('smtp_host', 'smtp.zoho.eu');
        SiteSetting::set('smtp_port', '587');
        SiteSetting::set('smtp_encryption', 'tls');
        SiteSetting::set('smtp_username', 'info@kosarticaret.com');
        SiteSetting::set('smtp_password', 'secret');
        SiteSetting::set('smtp_from_address', 'info@kosarticaret.com');
        SiteSetting::set('smtp_from_name', 'Koşar Ticaret');

        $admin = User::query()->where('is_admin', true)->first();
        $order = $this->makePendingOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.payment-reminder', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertNotNull($order->payment_reminder_sent_at);
        $this->assertTrue($order->logs()->where('type', 'payment_reminder')->exists());
        Mail::assertSent(\App\Mail\OrderPaymentReminderMail::class, 1);
    }

    #[Test]
    public function admin_order_detail_includes_payment_reminder_form_outside_update_form(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $order = $this->makePendingOrder();

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="payment-reminder-form"', $html);
        $this->assertStringContainsString('form="payment-reminder-form"', $html);
        $this->assertSame(1, substr_count($html, 'id="payment-reminder-form"'));
    }

    #[Test]
    public function admin_default_order_list_hides_pending_payment_orders(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $this->makePendingOrder();
        Order::query()->create([
            'order_number' => 'KOS-PAID001',
            'email' => 'paid@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kredi_karti',
            'customer_name' => 'Paid User',
            'shipping_address' => ['teslimat' => []],
            'subtotal' => 100,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertDontSee('KOS-TEST001', false)
            ->assertSee('KOS-PAID001', false);
    }

    #[Test]
    public function admin_can_filter_pending_payment_orders(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $this->makePendingOrder();
        Order::query()->create([
            'order_number' => 'KOS-PAID001',
            'email' => 'paid@example.com',
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'kredi_karti',
            'customer_name' => 'Paid User',
            'shipping_address' => ['teslimat' => []],
            'subtotal' => 100,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['pending_payment' => '1']))
            ->assertOk()
            ->assertSee('KOS-TEST001', false)
            ->assertDontSee('KOS-PAID001', false);
    }

    /** @param  array<string, mixed>  $overrides */
    private function makePendingOrder(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'order_number' => 'KOS-TEST001',
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

    private function paytrHash(string $merchantOid, string $status, string $totalAmount): string
    {
        return base64_encode(hash_hmac(
            'sha256',
            $merchantOid.'test-salt'.$status.$totalAmount,
            'test-key',
            true,
        ));
    }
}
