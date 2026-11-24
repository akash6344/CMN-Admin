<?php

namespace App\Support;

use App\Models\Dashboard\PropertyReview;
use App\Models\Housing\ProjectDetail;
use App\Models\PostProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin's Properties section.
 *  - Owner / agent listings: submitted → Operations reviews → Admin approves or raises a query → live.
 *  - Builder projects ("Builder projects" tab): reviewed by BRM only (CMN-Builder-Relationship → Project Approvals).
 *    Admin sees their real state straight from project_details — view only, no approval here.
 */
class PropertyIndex
{
    /** tab => [label, hint] — "pending" is what Operations has sent for Admin's approval */
    public const TABS = [
        'all' => ['All Listings', 'Every owner and agent listing'],
        'pending' => ['Pending', 'Reviewed by Operations — waiting for your approval'],
        'approved' => ['Approved', 'Approved and live on the website'],
        'rejected' => ['Rejected', 'Sent back — to Operations with your comment, or to the owner / agent for edits'],
        'with_ops' => ['With Operations', 'Submitted, Operations is still reviewing'],
        'builder' => ['Builder projects (reviewed by BRM)', 'Builder projects are reviewed by the BRM team — view only here'],
    ];

    /** Tabs that list owner / agent listings (the Operations → Admin flow). */
    public const LISTING_TABS = ['all', 'pending', 'approved', 'rejected', 'with_ops'];

    /** "Posted by" filter (listings) */
    public const SOURCES = ['' => 'All', 'owner' => 'Owners', 'agent' => 'Agents'];

    /**
     * Builder project state, read from project_details (BRM's decisions) — state => [label, badge class].
     * BRM: Approve → published · Request changes → rejected · Reject → archived · Take down → rejected with "Taken down: …".
     */
    public const PROJECT_STATES = [
        'with_brm' => ['With BRM (under review)', 'badge-pending'],
        'live' => ['Live', 'badge-approved'],
        'changes' => ['Changes requested', 'badge-rejected'],
        'rejected' => ['Rejected', 'badge-rejected'],
        'taken_down' => ['Taken down', 'badge-draft'],
    ];

    /** review status → Admin's stage (listings) */
    private const STAGE_OF_REVIEW = [
        'ready_for_admin' => 'awaiting', 'admin_query' => 'queries', 'pending' => 'with_ops', 'in_review' => 'with_ops',
        'changes_required' => 'edits', 'rejected' => 'edits', 'approved' => 'live',
    ];

    /** stage => [label, badge class] */
    public const STAGES = [
        'awaiting' => ['Pending', 'badge-pending'],
        'queries' => ['Sent back to Ops', 'badge-rejected'],
        'with_ops' => ['With Operations', 'badge-draft'],
        'edits' => ['Edits Requested', 'badge-rejected'],
        'live' => ['Approved', 'badge-approved'],
        'hidden' => ['Approved · Hidden', 'badge-draft'],
        'paused' => ['Paused', 'badge-draft'],
        'deleted' => ['Deleted', 'badge-rejected'],
    ];

    public static function reviewsAvailable(): bool
    {
        try {
            // Operations' review trail lives in CMN-Emp's DB (connection "ops"), not the website DB
            return Schema::connection('ops')->hasTable('property_reviews') && Schema::connection('ops')->hasColumn('property_reviews', 'kind');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function projectsAvailable(): bool
    {
        try {
            return Schema::connection('housing')->hasTable('project_details');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function awaitingCount(): int
    {
        try {
            return self::reviewsAvailable() ? PropertyReview::query()->where('kind', 'listing')->where('status', 'ready_for_admin')->count() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Builder projects BRM is reviewing right now (read-only count for Admin). */
    public static function withBrmCount(): int
    {
        try {
            return self::projectsAvailable() ? ProjectDetail::query()->where('status', 'under_review')->count() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return array<string, int> builder projects per state */
    public static function projectStateCounts(): array
    {
        $counts = array_fill_keys(array_keys(self::PROJECT_STATES), 0);
        if (! self::projectsAvailable()) {
            return $counts;
        }
        foreach (ProjectDetail::query()->where('status', '<>', 'draft')->get(['id', 'status', 'rejection_reason']) as $p) {
            $counts[self::projectState($p)] = ($counts[self::projectState($p)] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * [kind, id, created_at] of everything matching the filters, newest first.
     * The "builder" tab lists builder projects; every other tab lists owner / agent listings.
     *
     * @param  array{tab: string, source: string, type: string, q: string, brm?: string}  $filters
     * @return Collection<int, array{0: string, 1: int, 2: string}>
     */
    public static function rows(array $filters): Collection
    {
        $rows = $filters['tab'] === 'builder'
            ? (self::projectsAvailable() ? self::projects($filters)->get(['id', 'status', 'rejection_reason', 'created_at'])
                ->filter(fn ($p) => ($filters['brm'] ?? '') === '' || self::projectState($p) === $filters['brm'])
                ->map(fn ($p) => ['project', $p->id, (string) $p->created_at]) : collect())
            : self::listings($filters)->get(['id', 'created_at'])->map(fn ($l) => ['listing', $l->id, (string) $l->created_at]);

        return $rows->sortByDesc(fn ($r) => $r[2].sprintf('%010d', $r[1]))->values();
    }

    /**
     * @param  array{tab: string, source: string, type: string, q: string}  $filters
     */
    public static function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $rows = self::rows($filters);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $rows->forPage($page, $perPage);

        return new LengthAwarePaginator(self::hydrate($slice), $rows->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query(),
        ]);
    }

    /**
     * Each tab's total with this week vs last week (by submission date), for the stat cards.
     *
     * @return array<string, array{total: int, this_week: int, last_week: int, change: int|null, label: string, trend: string}>
     */
    public static function comparisons(array $filters): array
    {
        $weekStart = now()->startOfWeek();
        $lastWeekStart = (clone $weekStart)->subWeek();

        return collect(array_keys(self::TABS))->mapWithKeys(function (string $tab) use ($filters, $weekStart, $lastWeekStart) {
            $rows = self::rows(['tab' => $tab] + $filters);
            $in = fn ($from, $to) => $rows->filter(fn ($r) => $r[2] !== '' && $r[2] >= (string) $from && $r[2] < (string) $to)->count();

            return [$tab => WeeklyComparison::compare($rows->count(), $in($weekStart, now()->addSecond()), $in($lastWeekStart, $weekStart))];
        })->all();
    }

    /** One item for the detail panel, e.g. "listing-214" or "project-1". */
    public static function find(string $key): ?array
    {
        if (! preg_match('/^(listing|project)-(\d+)$/', $key, $m)) {
            return null;
        }
        if ($m[1] === 'project') {
            $project = self::projectsAvailable() ? ProjectDetail::query()->where('status', '<>', 'draft')->find((int) $m[2]) : null;

            return $project ? self::item('project', $project, null) : null;
        }
        $listing = PostProperty::query()->with('documents')->find((int) $m[2]);

        return $listing ? self::item('listing', $listing, self::reviewFor((int) $m[2], withEvents: true)) : null;
    }

    /** @return list<string> */
    public static function types(bool $projects = false): array
    {
        $types = $projects
            ? (self::projectsAvailable() ? ProjectDetail::query()->whereNotNull('project_type')->distinct()->pluck('project_type') : collect())
            : PostProperty::query()->whereNotNull('property_type')->where('property_type', '<>', '')->distinct()->pluck('property_type');

        return $types->map(fn ($t) => trim((string) $t))->filter()->unique()->sort()->values()->all();
    }

    /**
     * Everything the list and the detail show, the same shape for listings and projects.
     * Projects carry BRM's review (who, when, reason) instead of an Operations review.
     *
     * @return array<string, mixed>
     */
    public static function item(string $kind, PostProperty|ProjectDetail $model, ?PropertyReview $review): array
    {
        $d = self::describe($kind, $model);
        if ($kind === 'project') {
            $state = self::projectState($model);

            return $d + ['kind' => 'project', 'key' => 'project-'.$model->id, 'model' => $model, 'review' => null,
                'stage' => $state, 'stageLabel' => self::PROJECT_STATES[$state][0], 'stageBadge' => self::PROJECT_STATES[$state][1],
                'brm' => self::brmReview($model)];
        }
        $stage = self::stage($model, $review);

        return $d + ['kind' => $kind, 'key' => $kind.'-'.$model->id, 'model' => $model, 'review' => $review,
            'stage' => $stage, 'stageLabel' => self::STAGES[$stage][0], 'stageBadge' => self::STAGES[$stage][1], 'brm' => null];
    }

    /** A builder project's state from BRM's decision in project_details. */
    public static function projectState(ProjectDetail $p): string
    {
        return match ($p->status) {
            'published' => 'live',
            'archived' => 'rejected',
            'rejected' => str_starts_with((string) $p->rejection_reason, 'Taken down:') ? 'taken_down' : 'changes',
            default => 'with_brm',   // under_review (drafts are not listed)
        };
    }

    /**
     * Who at BRM decided, when, and why (project_details.reviewed_by / reviewed_at / rejection_reason).
     *
     * @return array{by: ?string, at: mixed, reason: ?string, url: string}
     */
    public static function brmReview(ProjectDetail $p): array
    {
        $at = $p->reviewed_at ?? null;

        return [
            'by' => filled($p->reviewed_by ?? null) ? (string) $p->reviewed_by : null,
            'at' => $at ? Carbon::parse($at) : null,
            'reason' => filled($p->rejection_reason) ? (string) $p->rejection_reason : null,
            'url' => rtrim((string) config('services.brm.url'), '/').'/project-approvals/'.$p->id,
        ];
    }

    /**
     * @return array{code:string, kindLabel:string, title:string, type:string, from:string, role:string, place:string, city:string, price:string, cover:?string, phone:?string, email:?string, rera:?string, description:?string, documents:list<string>, facts:array<string,?string>, created:mixed}
     */
    public static function describe(string $kind, PostProperty|ProjectDetail $model): array
    {
        if ($kind === 'project') {
            $p = $model;
            $units = DB::connection('housing')->table('unit_configurations')->where('project_detail_id', $p->id)->get();
            $min = (float) $units->where('price', '>', 0)->min('price');
            $max = (float) $units->max('price');
            $cover = rescue(fn () => DB::connection('housing')->table('project_media')->where('project_detail_id', $p->id)->orderByDesc('is_cover')->orderBy('sort_order')->value('file_url'), null, false);
            $files = rtrim((string) env('HOUSING_FILES_URL', 'http://localhost:8091'), '/');

            return [
                'code' => 'PR'.str_pad((string) $p->id, 3, '0', STR_PAD_LEFT), 'kindLabel' => 'Builder project', 'title' => (string) $p->name,
                'type' => trim($p->project_type.' · '.$p->project_status, ' ·'), 'from' => (string) $p->builder_name, 'role' => 'Builder',
                'place' => (string) $p->location, 'city' => (string) $p->location,
                'price' => $min ? PostProperty::money($min).($max > $min ? ' – '.PostProperty::money($max) : '') : 'Price not set',
                'cover' => $cover ? (str_starts_with($cover, 'http') ? $cover : $files.'/'.ltrim($cover, '/')) : null,
                'phone' => null, 'email' => null, 'rera' => $p->rera_number, 'description' => $p->description,
                'documents' => rescue(fn () => DB::connection('housing')->table('project_documents')->where('project_detail_id', $p->id)->pluck('title')->all(), [], false),
                'facts' => ['Unit types' => (string) $units->count(), 'Total units' => $p->total_units ? (string) $p->total_units : null, 'Towers' => $p->towers ? (string) $p->towers : null,
                    'Possession' => $p->possession_date?->format('M Y'), 'Land area' => $p->land_area],
                'created' => $p->created_at,
                'units' => $units->map(fn ($u) => ['type' => strtoupper((string) $u->unit_type), 'area' => (float) $u->built_up_area, 'price' => (float) $u->price])->all(),
            ];
        }
        $l = $model;

        return [
            'code' => $l->code(), 'kindLabel' => $l->submitterRole().' listing', 'title' => $l->property_title ?: 'Untitled listing',
            'type' => trim($l->property_type.($l->property_for ? ' · for '.ucfirst(strtolower((string) $l->property_for)) : ''), ' ·'),
            'from' => $l->ownerName(), 'role' => $l->submitterRole(), 'place' => $l->place(), 'city' => (string) $l->city,
            'price' => $l->formattedPrice(), 'cover' => $l->coverUrl(), 'phone' => $l->user_mobile, 'email' => $l->user_email,
            'rera' => $l->rera ?: $l->rera_id, 'description' => trim(strip_tags((string) $l->about_property)) ?: null,
            'documents' => rescue(fn () => ($l->relationLoaded('documents') ? $l->documents : $l->documents()->get())->map->displayName()->all(), [], false),
            'facts' => collect($l->specsForDisplay())->reject(fn ($s) => $s['value'] === '—')->mapWithKeys(fn ($s) => [$s['label'] => $s['value']])->all(),
            'created' => $l->created_at,
            'units' => [],
        ];
    }

    /** A listing's stage in the Operations → Admin flow. Paused / deleted on the website wins over any review. */
    public static function stage(PostProperty $model, ?PropertyReview $review): string
    {
        $state = ListingStatus::of($model);
        if (in_array($state, ['paused', 'deleted'], true)) {
            return $state;
        }
        if ($review && isset(self::STAGE_OF_REVIEW[$review->status])) {
            $stage = self::STAGE_OF_REVIEW[$review->status];
            if ($stage !== 'live') {
                return $stage;
            }
        }

        return match ($state) {
            'live' => strcasecmp((string) $model->show_on_website, 'show') === 0 ? 'live' : 'hidden',
            'rejected' => 'edits',
            default => 'with_ops',
        };
    }

    private static function reviewFor(int $listingId, bool $withEvents = false): ?PropertyReview
    {
        if (! self::reviewsAvailable()) {
            return null;
        }

        return PropertyReview::query()->where('kind', 'listing')->where('post_property_id', $listingId)
            ->when($withEvents, fn ($q) => $q->with('events'))->first();
    }

    /** @param  Collection<int, array{0:string,1:int,2:string}>  $slice */
    private static function hydrate(Collection $slice): Collection
    {
        $ids = fn (string $kind) => $slice->where(0, $kind)->pluck(1)->all();
        $listings = PostProperty::query()->whereIn('id', $ids('listing'))->get()->keyBy('id');
        $projects = $ids('project') ? ProjectDetail::query()->whereIn('id', $ids('project'))->get()->keyBy('id') : collect();
        $reviews = self::reviewsAvailable() && $ids('listing')
            ? PropertyReview::query()->where('kind', 'listing')->whereIn('post_property_id', $ids('listing'))->get()->keyBy('post_property_id')
            : collect();

        return $slice->map(function ($row) use ($listings, $projects, $reviews) {
            [$kind, $id] = $row;
            $model = $kind === 'listing' ? $listings[$id] ?? null : $projects[$id] ?? null;
            $review = $kind === 'listing' ? $reviews[$id] ?? null : null;

            return $model ? self::item($kind, $model, $review) : null;
        })->filter()->values();
    }

    /**
     * Listing ids with an Operations review in some statuses. The reviews are in another database (connection "ops"),
     * so this is an id list for whereIn, never a subquery / join.
     *
     * @return list<int>
     */
    private static function reviewed(string $kind, ?array $statuses = null): array
    {
        if (! self::reviewsAvailable()) {
            return [];
        }

        return PropertyReview::query()->where('kind', $kind)->when($statuses, fn ($x) => $x->whereIn('status', $statuses))
            ->whereNotNull('post_property_id')->pluck('post_property_id')->map(fn ($id) => (int) $id)->all();
    }

    private static function listings(array $f): Builder
    {
        // drafts (unfinished website forms) are never listed; paused / deleted show under "All" with their label, never in the pipeline tabs
        $q = PostProperty::query()->when(ListingStatus::tracksForm(), fn ($w) => $w->whereRaw("lower(trim(coalesce(form_status, ''))) <> ?", ['draft']));
        $all = self::reviewed('listing');

        match ($f['tab']) {
            'pending' => ListingStatus::scopeInPipeline($q->whereIn('id', self::reviewed('listing', ['ready_for_admin']))),
            'with_ops' => ListingStatus::scopeInPipeline($q->where(fn ($w) => $w->whereIn('id', self::reviewed('listing', ['pending', 'in_review']))
                ->orWhere(fn ($x) => ListingStatus::scopeAwaiting($x->whereNotIn('id', $all))))),
            'rejected' => ListingStatus::scopeInPipeline($q->where(fn ($w) => $w->whereIn('id', self::reviewed('listing', ['admin_query', 'changes_required', 'rejected']))
                ->orWhere(fn ($x) => $x->whereNotIn('id', $all)->whereRaw('lower(trim(post_status)) = ?', ['rejected'])))),
            'approved' => $q->whereRaw('lower(trim(post_status)) = ?', ['approved']),
            default => null,
        };
        if (in_array($f['source'], ['owner', 'agent'], true)) {
            // user_role can't tell owner from agent on the live website (always "buyer") — see ListingStatus::isAgent
            $f['source'] === 'agent' ? $q->whereIn('id', ListingStatus::agentListingIds()) : $q->whereNotIn('id', ListingStatus::agentListingIds());
        }
        if ($f['type'] !== '') {
            $q->where('property_type', $f['type']);
        }
        if ($f['q'] !== '') {
            $term = '%'.$f['q'].'%';
            $id = preg_match('/^p0*(\d+)$/i', $f['q'], $m) ? (int) $m[1] : -1;
            $q->where(fn ($w) => $w->where('property_title', 'like', $term)->orWhere('property_id', 'like', $term)->orWhere('user_name', 'like', $term)
                ->orWhere('city', 'like', $term)->orWhere('location', 'like', $term)->orWhere('property_type', 'like', $term)->orWhere('id', $id));
        }

        return $q;
    }

    /** Builder projects BRM has received (drafts are still with the builder). */
    private static function projects(array $f): Builder
    {
        $q = ProjectDetail::query()->where('status', '<>', 'draft');
        if ($f['type'] !== '') {
            $q->where('project_type', $f['type']);
        }
        if ($f['q'] !== '') {
            $term = '%'.$f['q'].'%';
            $id = preg_match('/^pr0*(\d+)$/i', $f['q'], $m) ? (int) $m[1] : -1;
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('builder_name', 'like', $term)->orWhere('location', 'like', $term)
                ->orWhere('rera_number', 'like', $term)->orWhere('project_type', 'like', $term)->orWhere('id', $id));
        }

        return $q;
    }
}
