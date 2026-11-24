<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_asks_for_mobile_number(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Mobile Number / Email')
            ->assertSee('Admin Portal')
            ->assertSee('Secure Access')
            ->assertSee('Forgot Password?')
            ->assertDontSee('Login with OTP')   // OTP isn't built — no fake controls on the sign-in page
            ->assertDontSee('Two-Factor Authentication (OTP)')
            ->assertDontSee('Email address');
    }

    public function test_admin_can_login_with_mobile_and_password(): void
    {
        $admin = User::factory()->create([
            'admin_role' => 'admin',
            'mobile' => '9988776655',
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('login.store'), [
            'login' => '9988776655',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_login_with_email_and_password(): void
    {
        $admin = User::factory()->create([
            'admin_role' => 'admin',
            'email' => 'admin@cmnhousing.test',
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('login.store'), [
            'login' => 'admin@cmnhousing.test',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_for_email_is_rejected_and_keeps_entered_login(): void
    {
        User::factory()->create([
            'admin_role' => 'admin',
            'email' => 'admin@cmnhousing.test',
            'password' => Hash::make('password123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => 'admin@cmnhousing.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login' => 'The provided credentials do not match our records.'])
            ->assertSessionHasInput('login', 'admin@cmnhousing.test');

        $this->assertGuest();
    }

    public function test_non_admin_cannot_login_with_email(): void
    {
        User::factory()->create([
            'role' => 'seller',
            'admin_role' => null,
            'email' => 'seller@cmnhousing.test',
            'password' => Hash::make('password123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => 'seller@cmnhousing.test',
                'password' => 'password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login' => 'This account is not authorized to access the admin dashboard.']);

        $this->assertGuest();
    }

    public function test_empty_login_field_shows_mobile_or_email_message(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), ['login' => '', 'password' => 'password123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login' => 'The mobile number or email field is required.']);

        $this->assertGuest();
    }

    public function test_non_admin_cannot_login_with_mobile(): void
    {
        User::factory()->create([
            'role' => 'seller',
            'admin_role' => null,
            'mobile' => '9111222333',
            'password' => Hash::make('password123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => '9111222333',
                'password' => 'password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
