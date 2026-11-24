<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Settings for the signed-in admin: profile and password (saved through ProfileController),
 * which alerts they see, and the team of admins who can sign in to this portal (managed by super admins — see User::canManageTeam).
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.settings', [
            'user' => $request->user(),
            'alerts' => User::ALERTS,
            'canManageTeam' => $request->user()->canManageTeam(),
            'team' => User::query()
                ->whereRaw(User::adminRoleSql())
                ->orderByRaw("CASE WHEN lower(trim(coalesce(status, 'Active'))) = 'active' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'alerts' => ['nullable', 'array'],
            'alerts.*' => ['string', Rule::in(array_keys(User::ALERTS))],
        ]);

        $on = $request->input('alerts', []);
        $prefs = collect(User::ALERTS)->mapWithKeys(fn ($_, string $key) => [$key => in_array($key, $on, true)])->all();

        $user = $request->user();
        $user->update(['notification_preferences' => $prefs]);

        $off = array_keys(array_filter($prefs, fn (bool $v) => ! $v));
        AuditLog::record('settings.notifications_updated', "{$user->name} updated their alert preferences.", $user, [
            'on' => implode(', ', array_keys(array_filter($prefs))) ?: 'none',
            'off' => implode(', ', $off) ?: 'none',
        ]);

        return redirect()->to(route('settings.index').'#notifications')->with('success', 'Notification preferences saved.');
    }

    public function storeTeamMember(Request $request): RedirectResponse
    {
        if ($stop = $this->onlyTeamManagers($request)) {
            return $stop;
        }
        $data = $request->validateWithBag('team', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['nullable', 'string', 'max:32', Rule::unique('users', 'mobile')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $member = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => filled($data['mobile'] ?? null) ? $data['mobile'] : null,
            'password' => $data['password'],
            'admin_role' => 'admin',
            'status' => 'Active',
        ]);

        AuditLog::record('team.admin_added', "Admin {$member->name} added to the team.", $member, ['email' => $member->email]);

        return redirect()->to(route('settings.index').'#team')->with('success', "{$member->name} can now sign in to the Admin portal.");
    }

    public function toggleTeamMember(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);
        if ($stop = $this->onlyTeamManagers($request)) {
            return $stop;
        }

        if ($user->is($request->user())) {
            return redirect()->to(route('settings.index').'#team')->withErrors(['team' => 'You can’t deactivate your own account.']);
        }

        $activate = ! $user->isActive();
        $user->update(['status' => $activate ? 'Active' : 'Inactive']);

        if (! $activate) {
            $user->forceFill(['remember_token' => null])->save();   // ends "remember me" sign-ins
        }

        $verb = $activate ? 'reactivated' : 'deactivated';
        AuditLog::record('team.admin_'.$verb, "Admin {$user->name} {$verb}.", $user, ['email' => $user->email]);

        return redirect()->to(route('settings.index').'#team')->with('success', "{$user->name} {$verb}.");
    }

    /** Team changes are for super admins (or any admin while no active super admin exists). */
    private function onlyTeamManagers(Request $request): ?RedirectResponse
    {
        return $request->user()->canManageTeam()
            ? null
            : redirect()->to(route('settings.index').'#team')->withErrors(['team' => 'Only a super admin can add, deactivate or reactivate admins.']);
    }
}
