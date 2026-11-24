<?php

namespace App\Models\Housing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Support ticket in the shared housing DB. Builders raise them in the builder portal, BRM works the queue,
 * and Admin sees every ticket on the Complaints page. Keep the vocab in sync with CMN-housing / CMN-Builder-Relationship.
 */
class SupportTicket extends Model
{
    protected $connection = 'housing';

    protected $guarded = ['id'];

    public const CATEGORIES = [
        'technical' => 'Technical', 'project_upload' => 'Project Upload', 'payment' => 'Payment', 'kyc' => 'KYC',
        'marketing' => 'Marketing', 'agreement' => 'Agreement', 'complaint' => 'Complaint',
    ];

    /** priority => [label, SLA hours] */
    public const PRIORITIES = ['critical' => ['Critical', 4], 'high' => ['High', 24], 'medium' => ['Medium', 48], 'low' => ['Low', 72]];

    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In Progress', 'escalated' => 'Escalated', 'resolved' => 'Resolved'];

    public const OPEN = ['open', 'in_progress', 'escalated'];

    /** Which portal a ticket came from, by who raised it. */
    public const SOURCES = ['builder' => 'Builder portal', 'brm' => 'Logged by BRM', 'admin' => 'Logged by Admin'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'first_response_at' => 'datetime', 'resolved_at' => 'datetime', 'builder_seen_at' => 'datetime', 'brm_seen_at' => 'datetime'];
    }

    public static function available(): bool
    {
        return rescue(fn () => Schema::connection('housing')->hasTable('support_tickets'), false, false);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->oldest();
    }

    /** @param  Builder<SupportTicket>  $query */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at?->isPast();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority][0] ?? ucfirst((string) $this->priority);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->raised_by] ?? ucfirst((string) $this->raised_by);
    }

    /** Matches the card design's three levels: high / medium / low. */
    public function level(): string
    {
        return match ($this->priority) {
            'critical', 'high' => 'high',
            'medium' => 'medium',
            default => 'low',
        };
    }

    /** CSS class for the status text in the card design. */
    public function statusClass(): string
    {
        return match ($this->status) {
            'open' => 'open',
            'resolved' => 'resolved',
            'escalated' => 'escalated',
            default => 'in-progress',
        };
    }

    public function projectName(): ?string
    {
        return $this->project_id ? ProjectDetail::query()->whereKey($this->project_id)->value('name') : null;
    }

    public static function slaDue(string $priority, $from = null): Carbon
    {
        return ($from ?? now())->copy()->addHours(self::PRIORITIES[$priority][1] ?? 48);
    }
}
