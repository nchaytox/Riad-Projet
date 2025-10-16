<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'guest@example.com',
            'password' => Hash::make('password'),
            'role' => 'Customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'guest@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'guest@example.com',
            'password' => Hash::make('password'),
            'role' => 'Customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'guest@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('failed');
        $this->assertGuest();
    }

    public function test_unverified_user_is_prompted_to_verify_email(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'Customer',
            'password' => Hash::make('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('failed');
        $this->assertGuest();
    }
}
