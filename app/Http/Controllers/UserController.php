<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PostProperty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $appUsers = User::query()
            ->whereNotNull('role')
            ->where('role', '<>', '')
            ->latest()
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();
        $this->attachListingCounts($appUsers->getCollection());

        return view('pages.users', [
            'employees' => User::query()
                ->whereNotNull('admin_role')
                ->where('admin_role', '<>', '')
                ->latest()
                ->paginate(10, ['*'], 'employees_page')
                ->withQueryString(),
            'appUsers' => $appUsers,
        ]);
    }

    /**
     * Website listings live in a separate database, so they can't be counted with a
     * subquery (withCount); count them with one grouped query on that connection instead.
     *
     * @param  Collection<int, User>  $users
     */
    private function attachListingCounts($users): void
    {
        $emails = $users->pluck('email')->filter()->map(fn ($e) => strtolower($e))->unique()->values();
        $counts = collect();
        if ($emails->isNotEmpty()) {
            try {
                $counts = PostProperty::query()
                    ->whereIn(DB::raw('lower(user_email)'), $emails->all())
                    ->selectRaw('lower(user_email) as email, count(*) as total')
                    ->groupByRaw('lower(user_email)')
                    ->pluck('total', 'email');
            } catch (\Throwable) {
                $counts = collect();
            }
        }
        $users->each(fn (User $u) => $u->setAttribute('post_properties_count', (int) ($counts[strtolower((string) $u->email)] ?? 0)));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedUser($request);
        $validated['status'] = $validated['status'] ?? 'Active';
        if ($stop = $this->adminAccountGuard($request, null, $validated['admin_role'] ?? null)) {
            return $stop;
        }

        $created = User::create($validated);

        AuditLog::record(
            'user.created',
            "User {$created->name} created.",
            $created,
            ['email' => $created->email],
        );

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatedUser($request, $user);
        if ($stop = $this->adminAccountGuard($request, $user, $validated['admin_role'] ?? null)) {
            return $stop;
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        AuditLog::record(
            'user.updated',
            "User {$user->name} updated.",
            $user,
            ['email' => $user->email],
        );

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($stop = $this->adminAccountGuard($request, $user, $user->admin_role)) {
            return $stop;
        }
        $nextStatus = strtolower((string) $user->status) === 'active' ? 'Inactive' : 'Active';
        $user->update(['status' => $nextStatus]);

        $label = $nextStatus === 'Active' ? 'activated' : 'blocked';

        AuditLog::record(
            'user.'.$label,
            "User {$user->name} {$label}.",
            $user,
            ['status' => $nextStatus],
        );

        return back()->with('success', "User {$label} successfully.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }
        if ($stop = $this->adminAccountGuard($request, $user, $user->admin_role)) {
            return $stop;
        }

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        AuditLog::record(
            'user.deleted',
            "User {$name} deleted.",
            null,
            ['email' => $email],
        );

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function properties(User $user): View
    {
        $properties = $user->postProperties()
            ->latest('created_at')
            ->latest('id')
            ->get();

        return view('pages.user-properties', [
            'user' => $user,
            'properties' => $properties,
        ]);
    }

    /**
     * Admin / super admin accounts are team management: only someone who can manage the team (User::canManageTeam)
     * may create, change, block or delete them — otherwise an admin could promote themselves to super admin here.
     */
    private function adminAccountGuard(Request $request, ?User $target, ?string $newAdminRole): ?RedirectResponse
    {
        $touchesAdmin = ($target?->isAdmin() ?? false)
            || in_array(User::normalizeRole($newAdminRole), User::ADMIN_ROLES, true);

        return $touchesAdmin && ! $request->user()->canManageTeam()
            ? back()->withErrors(['user' => 'Only a super admin can add, change or remove admin accounts.'])->withInput()
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUser(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'mobile' => ['required', 'string', 'max:255', Rule::unique('users', 'mobile')->ignore($user)],
            'city' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'required_without:admin_role', 'string', 'max:255'],
            'admin_role' => ['nullable', 'required_without:role', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
            'password' => $passwordRules,
        ]);
    }
}
