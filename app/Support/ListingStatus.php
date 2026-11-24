<?php

namespace App\Support;

use App\Models\PostProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What a website listing's status means — defined once, used by the queue, sync, filters and labels.
 *
 * Live website (cmn_laravel_13): post_status is "Approved", "Deleted", "Pause" or empty, and form_status is
 * "draft" (form not finished) or "Published". The local CMN-Dashboard copy has no form_status and uses "pending".
 *  - awaiting: post_status "pending", or form_status "Published" with an empty post_status
 *  - draft:    form_status "draft" — never queued, never counted
 *  - live (Approved), paused (Pause), deleted (Deleted) — paused / deleted are out of every pipeline
 *
 * user_role is "buyer" on every live listing (the website doesn't record owner vs agent), so an agent listing is
 * also recognised by its email / mobile matching an agent in CMN-Agent's users table.
 */
class ListingStatus
{
    public const LABELS = [
        'draft' => 'Draft', 'awaiting' => 'Awaiting review', 'live' => 'Approved', 'paused' => 'Paused',
        'deleted' => 'Deleted', 'rejected' => 'Rejected', 'other' => 'Pending',
    ];

    /** States that never enter the Operations → Admin pipeline. */
    public const OFF_PIPELINE = ['draft', 'paused', 'deleted'];

    /** @var array{emails: array<string, true>, phones: array<string, true>}|null */
    private ?array $agents = null;

    /** @var list<int>|null */
    private ?array $agentIds = null;

    public static function tracksForm(): bool
    {
        return WebsiteSchema::has('post_properties', 'form_status');
    }

    /** Listings submitted on the website and waiting for Operations. */
    public static function scopeAwaiting(Builder $q): Builder
    {
        $form = self::tracksForm();

        return $q->where(function (Builder $w) use ($form) {
            $w->whereRaw("lower(trim(coalesce(post_status, ''))) = ?", ['pending']);
            if ($form) {
                $w->orWhere(fn (Builder $x) => $x->whereRaw("lower(trim(coalesce(form_status, ''))) = ?", ['published'])
                    ->whereRaw("trim(coalesce(post_status, '')) = ''"));
            }
        })->when($form, fn (Builder $w) => $w->whereRaw("lower(trim(coalesce(form_status, ''))) <> ?", ['draft']));
    }

    /** Drop drafts, paused and deleted listings (pipelines and counts). */
    public static function scopeInPipeline(Builder $q): Builder
    {
        return $q->whereRaw("lower(trim(coalesce(post_status, ''))) not in ('pause', 'paused', 'deleted')")
            ->when(self::tracksForm(), fn (Builder $w) => $w->whereRaw("lower(trim(coalesce(form_status, ''))) <> ?", ['draft']));
    }

    public static function of(PostProperty $p): string
    {
        $post = strtolower(trim((string) $p->post_status));
        if (self::tracksForm() && strtolower(trim((string) $p->form_status)) === 'draft') {
            return 'draft';
        }

        return match (true) {
            $post === 'approved' => 'live',
            in_array($post, ['pause', 'paused'], true) => 'paused',
            $post === 'deleted' => 'deleted',
            $post === 'rejected' => 'rejected',
            $post === 'pending', $post === '' && self::tracksForm() && strcasecmp(trim((string) $p->form_status), 'published') === 0 => 'awaiting',
            default => 'other',
        };
    }

    public static function label(PostProperty $p): string
    {
        return self::LABELS[self::of($p)];
    }

    public static function inPipeline(PostProperty $p): bool
    {
        return ! in_array(self::of($p), self::OFF_PIPELINE, true);
    }

    /** Owner or agent: the listing's own role, else a match on CMN-Agent users by email / last 10 mobile digits. */
    public static function isAgent(object $p): bool
    {
        if (str_contains(strtolower((string) ($p->user_role ?? '').' '.($p->posted_as ?? '')), 'agent')) {
            return true;
        }
        $a = self::agents();
        $email = strtolower(trim((string) ($p->user_email ?? '')));
        $digits = substr((string) preg_replace('/\D/', '', (string) ($p->user_mobile ?? '')), -10);

        return ($email !== '' && isset($a['emails'][$email])) || (strlen($digits) === 10 && isset($a['phones'][$digits]));
    }

    /** @return list<int> ids of agent listings — for "posted by" filters (the agent DB can't be joined in SQL). */
    public static function agentListingIds(): array
    {
        $self = app(self::class);
        if ($self->agentIds === null) {
            $self->agentIds = PostProperty::query()->get()
                ->filter(fn ($p) => self::isAgent($p))->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return $self->agentIds;
    }

    /** @return array{emails: array<string, true>, phones: array<string, true>} */
    private static function agents(): array
    {
        $self = app(self::class);
        if ($self->agents === null) {
            $self->agents = ['emails' => [], 'phones' => []];
            try {
                foreach (DB::connection('agent')->table('users')->get(['email', 'phone']) as $u) {
                    $e = strtolower(trim((string) $u->email));
                    $d = substr((string) preg_replace('/\D/', '', (string) $u->phone), -10);
                    $e !== '' && $self->agents['emails'][$e] = true;
                    strlen($d) === 10 && $self->agents['phones'][$d] = true;
                }
            } catch (\Throwable) {
                // Agent DB unavailable: fall back to the listing's own role
            }
        }

        return $self->agents;
    }
}
