<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_registered_customer_appears_in_admin_list(): void
    {
        $customer = User::query()->create([
            'name' => 'Ayşe Müşteri',
            'email' => 'ayse@ornek.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
        ]);

        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Ayşe Müşteri', false)
            ->assertSee('ayse@ornek.com', false);
    }

    public function test_registration_flow_creates_listable_customer(): void
    {
        $this->post('/kayit', [
            'name' => 'Yeni Kayıt',
            'email' => 'yeni@musteri.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('account.index'));

        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Yeni Kayıt', false)
            ->assertSee('yeni@musteri.test', false);
    }
}
