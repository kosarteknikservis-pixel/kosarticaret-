<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_storefront_returns_maintenance_page_when_enabled(): void
    {
        SiteSetting::set('shop_maintenance_enabled', '1');
        SiteSetting::set('shop_maintenance_title', 'Test bakım');
        SiteSetting::set('shop_maintenance_message', 'Kısa süre sonra açılacağız.');

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Test bakım', false)
            ->assertSee('Kısa süre sonra açılacağız.', false);
    }

    public function test_admin_routes_remain_available_during_maintenance(): void
    {
        SiteSetting::set('shop_maintenance_enabled', '1');

        $this->get('/yonetim/giris')->assertOk();
        $this->get('/admin')->assertRedirect('/yonetim/giris');
    }

    public function test_expired_admin_session_redirects_to_admin_login(): void
    {
        $this->get('/yonetim')
            ->assertRedirect('/yonetim/giris');

        $this->get('/hesabim')
            ->assertRedirect('/giris');
    }

    public function test_admin_user_can_preview_storefront_during_maintenance(): void
    {
        SiteSetting::set('shop_maintenance_enabled', '1');

        $admin = User::query()->where('is_admin', true)->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk();
    }
}
