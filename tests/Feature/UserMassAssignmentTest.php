<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function is_admin_cannot_be_mass_assigned_to_true(): void
    {
        $user = User::query()->create([
            'name' => 'Mass Assign',
            'email' => 'mass-assign@example.com',
            'password' => 'secret-pass',
            'is_admin' => true,
        ]);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }
}
