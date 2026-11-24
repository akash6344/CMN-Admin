<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'mobile', 'profile_photo_path', 'city', 'location', 'state', 'role', 'admin_role', 'status', 'password', 'notification_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Alerts an admin can switch on or off in Settings → Notifications.
     * They drive the bell in the top bar (Admin sends no emails to admins today).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const ALERTS = [
        'property_approvals' => ['Properties awaiting approval', 'Owner and agent listings Operations has reviewed and sent up for your decision'],
        'deletion_requests' => ['Delete requests', 'Owners asking for a listing to be removed'],
        'agent_payouts' => ['Agent payouts', 'Payout requests from agents that need processing'],
        'complaints' => ['Escalated complaints', 'Support tickets escalated to a senior manager'],
    ];

    /** Every alert is on until the admin turns it off. */
    public function wantsAlert(string $key): bool
    {
        return (bool) (($this->notification_preferences ?? [])[$key] ?? true);
    }

    /** admin_role values that may sign in to this portal. The Users page stores "super admin"; "super_admin" / "superadmin" are accepted too. */
    public const ADMIN_ROLES = ['admin', 'superadmin'];

    /** "Super Admin" / "super_admin" / "super-admin" → "superadmin" */
    public static function normalizeRole(?string $role): string
    {
        return (string) preg_replace('/[\s_\-]+/', '', strtolower(trim((string) $role)));
    }

    /** Admins and super admins can sign in and do everything an admin can. */
    public function isAdmin(): bool
    {
        return in_array(self::normalizeRole($this->admin_role), self::ADMIN_ROLES, true);
    }

    public function isSuperAdmin(): bool
    {
        return self::normalizeRole($this->admin_role) === 'superadmin';
    }

    /** SQL for "admin_role is admin or super admin" (same normalisation as normalizeRole). */
    public static function adminRoleSql(): string
    {
        return "replace(replace(replace(lower(trim(coalesce(admin_role, ''))), ' ', ''), '_', ''), '-', '') in ('admin', 'superadmin')";
    }

    public static function hasActiveSuperAdmin(): bool
    {
        return self::query()->whereRaw("replace(replace(replace(lower(trim(coalesce(admin_role, ''))), ' ', ''), '_', ''), '-', '') = 'superadmin'")
            ->whereRaw("lower(trim(coalesce(status, 'Active'))) = 'active'")->exists();
    }

    /**
     * Who may add, deactivate and reactivate admins (Settings → Team, and admin accounts on the Users page):
     * super admins only — but while no active super admin exists, every admin, so the portal is never locked out of team management.
     */
    public function canManageTeam(): bool
    {
        return $this->isAdmin() && ($this->isSuperAdmin() || ! self::hasActiveSuperAdmin());
    }

    public function isActive(): bool
    {
        return strtolower(trim((string) ($this->status ?: 'Active'))) === 'active';
    }

    public function postProperties(): HasMany
    {
        // website listings are matched to the Admin user by email (separate account systems)
        return $this->hasMany(PostProperty::class, 'user_email', 'email');
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return '/storage/'.$this->profile_photo_path;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }
}
