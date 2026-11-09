<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_registration_sends_email_verification_notification(): void
    {
        Notification::fake();

        $this->post('/kayit', [
            'name' => 'Doğrulama Kullanıcı',
            'email' => 'dogrula@ornek.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('account.index'));

        $user = User::query()->where('email', 'dogrula@ornek.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_claims_matching_guest_orders_and_leaves_others(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'Musteri@Ornek.com',
            'is_admin' => false,
        ]);

        $matching = Order::query()->create([
            'user_id' => null,
            'order_number' => 'KOS-GUEST-MATCH',
            'email' => 'musteri@ornek.com',
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

        $other = Order::query()->create([
            'user_id' => null,
            'order_number' => 'KOS-GUEST-OTHER',
            'email' => 'baska@ornek.com',
            'status' => 'odeme_bekliyor',
            'payment_status' => 'bekliyor',
            'payment_method' => 'kredi_karti',
            'customer_name' => 'Other User',
            'phone' => '5550000001',
            'shipping_address' => ['teslimat' => ['ad' => 'Other', 'soyad' => 'User']],
            'subtotal' => 1000,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 1000,
            'sales_channel' => 'website',
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('account.index'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame($user->id, $matching->fresh()->user_id);
        $this->assertNull($other->fresh()->user_id);
    }
}
