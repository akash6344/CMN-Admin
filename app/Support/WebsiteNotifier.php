<?php

namespace App\Support;

use App\Models\PostProperty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Drops a message in the listing owner's CMNHousing (website) notification inbox.
 *
 * Live website: Laravel's database-notifications table (uuid id, type, notifiable_type/_id, JSON data) read by the
 * website as App\Notifications\ActivityNotification. The local CMN-Dashboard copy still has the old
 * user_id / title / body table — that shape is written when notifiable_id is missing.
 */
class WebsiteNotifier
{
    /**
     * @param  string  $type  e.g. "property.changes_requested", "property.approved"
     * @param  string  $tone  warning | success | info
     */
    public static function listing(PostProperty $p, string $type, string $title, string $message, string $tone = 'info', string $icon = 'fas fa-house'): bool
    {
        if (! (int) $p->user_id || ! WebsiteSchema::hasTable('notifications')) {
            return false;
        }
        $now = now();

        if (WebsiteSchema::has('notifications', 'notifiable_id')) {
            $data = array_filter([
                'category' => 'property', 'type' => $type, 'title' => $title, 'message' => $message, 'icon' => $icon, 'tone' => $tone,
                'url' => self::listingUrl($p),
                'related_type' => 'post_property', 'related_id' => (int) $p->id, 'property_title' => $p->property_title ?: null,
                'dedupe_key' => $type.'.'.$p->id.'.'.$now->timestamp,
            ], fn ($v) => $v !== null && $v !== '');

            DB::connection('dashboard')->table('notifications')->insert([
                'id' => (string) Str::uuid(), 'type' => 'App\\Notifications\\ActivityNotification',
                'notifiable_type' => 'App\\Models\\User', 'notifiable_id' => (int) $p->user_id,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'read_at' => null,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            return true;
        }

        DB::connection('dashboard')->table('notifications')->insert([
            'user_id' => $p->user_id, 'title' => $title.': '.mb_substr((string) $p->property_title, 0, 80), 'body' => $message,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return true;
    }

    /** The listing's page on the website, when WEBSITE_LISTING_URL is set (e.g. "https://cmnhousing.com/property/{id}"). */
    public static function listingUrl(PostProperty $p): ?string
    {
        $tpl = (string) config('services.website.listing_url');

        return $tpl !== '' ? strtr($tpl, ['{id}' => (string) $p->id, '{property_id}' => (string) ($p->property_id ?: $p->id)]) : null;
    }
}
