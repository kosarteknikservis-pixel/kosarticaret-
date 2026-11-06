<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_lists_order_owned_by_user_id(): void
    {
        $user = User::factory()->create([
            'email' => 'musteri-a@example.com',
            'is_admin' => false,
        ]);

        $this->makeOrder($user, [
            'user_id' => $user->id,
            'order_number' => 'KOS-MINE001',
            'email' => $user->email,
        ]);

        $this->actingAs($user)
            ->get('/hesabim')
            ->assertOk()
            ->assertSee('KOS-MINE001', false);

        $this->actingAs($user)
            ->get('/hesabim/siparis/KOS-MINE001')
            ->assertOk()
            ->assertSee('KOS-MINE001', false);
    }

    public function test_guest_order_with_same_email_is_hidden_and_returns_not_found(): void
    {
        $user = User::factory()->create([
            'email' => 'musteri-a@example.com',
            'is_admin' => false,
        ]);

        $this->makeOrder($user, [
            'user_id' => null,
            'order_number' => 'KOS-GUEST01',
            'email' => $user->email,
        ]);

        $this->actingAs($user)
            ->get('/hesabim')
            ->assertOk()
            ->assertDontSee('KOS-GUEST01', false);

        $this->actingAs($user)
            ->get('/hesabim/siparis/KOS-GUEST01')
            ->assertNotFound();
    }

    public function test_email_change_without_current_password_fails_validation(): void
    {
        $user = User::factory()->create([
            'name' => 'Ayşe Müşteri',
            'email' => 'musteri-a@example.com',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from('/hesabim/profil')
            ->put('/hesabim/profil', [
                'name' => 'Ayşe Müşteri',
                'email' => 'yeni-adres@example.com',
            ])
            ->assertRedirect('/hesabim/profil')
            ->assertSessionHasErrors('current_password');

        $fresh = $user->fresh();
        $this->assertSame('musteri-a@example.com', $fresh->email);
        $this->assertNotNull($fresh->email_verified_at);
    }

    public function test_email_change_with_current_password_clears_verification(): void
    {
        $user = User::factory()->create([
            'name' => 'Ayşe Müşteri',
            'email' => 'musteri-a@example.com',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->put('/hesabim/profil', [
                'name' => 'Ayşe Müşteri',
                'email' => 'yeni-adres@example.com',
                'current_password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('yeni-adres@example.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
    }

    /** @param  array<string, mixed>  $overrides */
    private function makeOrder(User $user, array $overrides): Order
    {
        return Order::query()->create(array_merge([
            'user_id' => $user->id,
            'order_number' => 'KOS-ORDER01',
            'email' => $user->email,
            'status' => 'hazirlaniyor',
            'payment_status' => 'basarili',
            'payment_method' => 'havale',
            'customer_name' => $user->name,
            'shipping_address' => ['teslimat' => []],
            'subtotal' => 100,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 100,
        ], $overrides));
    }
}
