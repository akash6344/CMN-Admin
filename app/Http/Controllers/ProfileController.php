<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('pages.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:32', Rule::unique('users', 'mobile')->ignore($user->id)],
        ]);

        $validated['mobile'] = filled($validated['mobile'] ?? null) ? $validated['mobile'] : null;

        $user->fill($validated);
        $changed = array_keys($user->getDirty());
        $user->save();

        if ($changed !== []) {
            AuditLog::record('profile.updated', "{$user->name} updated their profile (".implode(', ', $changed).').', $user, ['fields' => implode(', ', $changed)]);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $path = $validated['photo']->store('profile-photos', 'public');

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update(['profile_photo_path' => $path]);
        AuditLog::record('profile.photo_updated', "{$user->name} changed their profile photo.", $user);

        return back()->with('success', 'Profile photo updated successfully.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
            AuditLog::record('profile.photo_removed', "{$user->name} removed their profile photo.", $user);
        }

        return back()->with('success', 'Profile photo removed.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);
        AuditLog::record('profile.password_changed', "{$request->user()->name} changed their password.", $request->user());

        return back()->with('success', 'Password changed successfully.');
    }
}
