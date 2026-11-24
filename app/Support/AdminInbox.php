<?php

namespace App\Support;

use App\Models\Housing\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Admin's inbox: housing app_notifications rows with audience "admin", written by Operations (listing hand-overs)
 * and the builder portal (complaints / critical tickets). One shared inbox for the Admin team — read state is shared.
 */
class AdminInbox
{
    /** link prefix => Settings → Notifications key that switches it off in the bell */
    private const PREFERENCE_BY_LINK = [
        '/properties' => 'property_approvals',
        '/complaints' => 'complaints',
        '/deletion-requests' => 'deletion_requests',
        '/agent-earnings' => 'agent_payouts',
    ];

    public static function available(): bool
    {
        return rescue(fn () => Schema::connection('housing')->hasTable('app_notifications'), false, false);
    }

    /** @return Builder<AppNotification> */
    public static function query(): Builder
    {
        return AppNotification::query()->where('audience', 'admin');
    }

    /** Which alert preference a notification belongs to, if any. */
    public static function preferenceFor(AppNotification $n): ?string
    {
        $path = '/'.ltrim((string) parse_url((string) $n->link, PHP_URL_PATH), '/');
        foreach (self::PREFERENCE_BY_LINK as $prefix => $key) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Unread items for the bell, minus the kinds this admin switched off.
     * With $hideHandovers (the bell already shows the computed "N properties waiting for your approval" line),
     * Operations' per-listing hand-over notes (link /properties) are left out so they aren't counted twice.
     *
     * @return array{unread: int, items: Collection<int, AppNotification>}
     */
    public static function bellFor(User $user, bool $hideHandovers = false): array
    {
        if (! self::available()) {
            return ['unread' => 0, 'items' => collect()];
        }

        return rescue(function () use ($user, $hideHandovers) {
            $wanted = fn (AppNotification $n) => ($key = self::preferenceFor($n)) === null
                || ($user->wantsAlert($key) && ! ($hideHandovers && $key === 'property_approvals'));
            $unread = self::query()->whereNull('read_at')->latest()->latest('id')->limit(200)->get()->filter($wanted);

            return ['unread' => $unread->count(), 'items' => $unread->take(6)->values()];
        }, ['unread' => 0, 'items' => collect()], false);
    }
}
