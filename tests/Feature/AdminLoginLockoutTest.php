<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function email_is_locked_after_ten_failed_attempts_across_ips(): void
    {
        User::factory()->create([
            'email' => 'locked-admin@example.com',
            'password' => 'secret-pass',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'email' => 'other-admin@example.com',
            'password' => 'secret-pass',
            'is_admin' => true,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$i])
                ->from(route('admin.login'))
                ->post(route('admin.login'), [
                    'email' => 'locked-admin@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertSessionHasErrors('password');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.11'])
            ->from(route('admin.login'))
            ->post(route('admin.login'), [
                'email' => 'locked-admin@example.com',
                'password' => 'secret-pass',
            ])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Çok fazla başarısız deneme',
            (string) session('errors')->first('email')
        );

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
            ->from(route('admin.login'))
            ->post(route('admin.login'), [
                'email' => 'other-admin@example.com',
                'password' => 'secret-pass',
            ])
            ->assertRedirect(route('admin.dashboard'));

        RateLimiter::clear('admin-login:locked-admin@example.com');
        RateLimiter::clear('admin-login:other-admin@example.com');
    }
}
