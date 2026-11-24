<?php

namespace App\Models\Housing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Buyer leads (housing DB table `leads`), written by the website, agent portal and builder portal.
 * Admin only reads them — builders and BRM work the leads in their own portals.
 */
class Lead extends Model
{
    protected $connection = 'housing';

    protected $table = 'leads';

    protected $guarded = ['id'];

    /** status => label (same set the builder portal uses) */
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'site_visit' => 'Site Visit',
        'negotiating' => 'Negotiating',
        'closed' => 'Closed',
        'lost' => 'Lost',
    ];

    public const PLATFORMS = [
        'dashboard' => 'Website',
        'agent_portal' => 'Agent portal',
        'builder_portal' => 'Builder portal',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public static function available(): bool
    {
        return rescue(fn () => Schema::connection('housing')->hasTable('leads'), false, false);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectDetail::class, 'project_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? Str::title(str_replace('_', ' ', (string) $this->status));
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'closed' => 'badge-approved',
            'lost' => 'badge-rejected',
            'negotiating' => 'badge-negotiating',
            'new' => 'badge-pending',
            default => 'badge-active',
        };
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->source_platform] ?? Str::title(str_replace('_', ' ', (string) $this->source_platform));
    }

    public function sourceLabel(): string
    {
        return $this->source_detail ?: Str::title(str_replace('_', ' ', (string) $this->source));
    }

    /** Admin's property page for what the lead is interested in, if there is one. */
    public function propertyKey(): ?string
    {
        return match (true) {
            (bool) $this->project_id => 'project-'.$this->project_id,
            (bool) $this->post_property_id => 'listing-'.$this->post_property_id,
            default => null,
        };
    }
}
