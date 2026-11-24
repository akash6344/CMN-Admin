<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/** Forgot password: email a one-time link, then let the user choose a new password. */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        try {
            $status = Password::sendResetLink(['email' => trim($data['email'])]);
        } catch (\Throwable $e) {
            Log::error('Password reset email failed', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors(['email' => 'We couldn’t send the email right now. Please try again in a minute.']);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'A reset link was just sent. Please wait a minute before asking again.']);
        }

        // Same message whether or not the account exists, so emails can't be probed.
        return back()->with('status', 'If '.trim($data['email']).' has an account, a reset link is on its way. Check your inbox (and spam). The link works for '.config('auth.passwords.users.expire').' minutes.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($data + ['password_confirmation' => $request->input('password_confirmation')], function ($user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => $status === Password::INVALID_TOKEN
                ? 'This reset link has expired or was already used. Ask for a new one.'
                : 'We couldn’t find an account with that email.']);
        }

        return redirect()->route('login')->with('status', 'Password changed. Sign in with your new password.');
    }
}
