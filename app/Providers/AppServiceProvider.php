<?php

namespace App\Providers;

use App\Support\ListingStatus;
use App\Support\WebsiteSchema;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // per-request caches of the website DB's columns and of the agent contact list
        $this->app->scoped(WebsiteSchema::class);
        $this->app->scoped(ListingStatus::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Password reset email, branded per portal (APP_NAME) and sent via the shared Gmail SMTP.
        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
            $portal = config('app.name');

            return (new MailMessage)
                ->subject('Reset your '.$portal.' password')
                ->greeting('Hi '.(strtok((string) $user->name, ' ') ?: 'there').',')
                ->line('We received a request to reset the password for your '.$portal.' account.')
                ->action('Set a new password', $url)
                ->line('This link expires in '.config('auth.passwords.users.expire').' minutes and can be used once.')
                ->line('If you didn\'t ask for this, you can ignore this email — your password won\'t change.')
                ->salutation('— Team CMNHousing');
        });
    }
}
