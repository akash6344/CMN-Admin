<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [], ['login' => 'mobile number or email']);

        $loginColumn = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $user = User::query()->where($loginColumn, $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->getAuthPassword())) {
            return back()->withErrors(['login' => 'The provided credentials do not match our records.'])
                ->onlyInput('login');
        }

        if (! $user->isAdmin()) {
            return back()->withErrors(['login' => 'This account is not authorized to access the admin dashboard.'])
                ->onlyInput('login');
        }

        if (! $user->isActive()) {
            return back()->withErrors(['login' => 'This admin account has been deactivated. Ask another admin to reactivate it.'])
                ->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
