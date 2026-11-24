<?php

namespace App\Models\Housing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Shared notification inbox (housing DB table app_notifications) used by BRM, the builder portal and the agent portal.
 * Same class in every app — keep them in sync: CMN-Builder-Relationship, CMN-housing, CMN-Agent, CMN-Emp app/Models/AppNotification.php, CMN-Admin app/Models/Housing/AppNotification.php.
 */
class AppNotification extends Model
{
    protected $connection = 'housing';

    protected $table = 'app_notifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /** Write one notification. With a dedupe key the same alert is never stored twice. */
    public static function send(string $audience, string $type, string $title, ?string $body = null, ?string $link = null, string $tone = 'info', ?int $userId = null, ?string $company = null, ?string $dedupe = null): ?self
    {
        $attrs = [
            'audience' => $audience, 'type' => $type, 'title' => mb_substr($title, 0, 150), 'body' => $body ? mb_substr($body, 0, 500) : null,
            'link' => $link, 'tone' => $tone, 'user_id' => $userId, 'company' => $company ? trim($company) : null,
        ];
        try {
            return $dedupe
                ? self::query()->firstOrCreate(['dedupe_key' => mb_substr($dedupe, 0, 150)], $attrs)
                : self::query()->create($attrs);
        } catch (\Throwable $e) {
            report($e);   // a notification must never break the action that triggered it

            return null;
        }
    }

    /** Operations team inbox (CMN-Emp). */
    public function scopeForOps(Builder $q): Builder
    {
        return $q->where('audience', 'ops');
    }

    public function scopeForBrm(Builder $q): Builder
    {
        return $q->where('audience', 'brm');
    }

    /** A builder's inbox: addressed to their account, or to their company name. */
    public function scopeForBuilder(Builder $q, ?int $userId, ?string $company): Builder
    {
        return $q->where('audience', 'builder')->where(function ($w) use ($userId, $company) {
            $w->where('user_id', $userId ?? 0);
            if ($company) {
                $w->orWhere(fn ($x) => $x->whereNull('user_id')->whereRaw('lower(trim(company)) = ?', [strtolower(trim($company))]));
            }
        });
    }

    public function scopeForAgent(Builder $q, int $agentId): Builder
    {
        return $q->where('audience', 'agent')->where('user_id', $agentId);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /** "2m ago" · "3h ago" · "Yesterday" · "12 Jul" */
    public function when(): string
    {
        $at = $this->created_at ?? Carbon::now();

        return match (true) {
            $at->gt(now()->subHours(20)) => $at->diffForHumans(short: true),
            $at->isYesterday() => 'Yesterday',
            default => $at->format('j M'),
        };
    }
}
