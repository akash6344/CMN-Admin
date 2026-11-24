<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password_and_shows_no_demo_credentials(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'), false)->assertDontSee('Demo:');
    }

    public function test_forgot_password_emails_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'person@example.com']);

        $this->post('/forgot-password', ['email' => 'person@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $mail = $n->toMail($user);

            return str_contains($mail->actionUrl, '/reset-password/') && str_contains($mail->subject, 'password');
        });
    }

    public function test_unknown_email_gets_the_same_message(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_reset_link_sets_a_new_password(): void
    {
        $user = User::factory()->create(['email' => 'person@example.com']);
        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email=person@example.com')->assertOk()->assertSee('Set a new password');
        $this->post('/reset-password', ['token' => $token, 'email' => 'person@example.com', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123'])
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
    }

    public function test_bad_token_is_rejected(): void
    {
        User::factory()->create(['email' => 'person@example.com']);
        $this->from('/reset-password/bad')->post('/reset-password', ['token' => 'bad', 'email' => 'person@example.com', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123'])
            ->assertSessionHasErrors('email');
    }
}
