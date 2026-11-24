<?php

namespace App\Models;

use Database\Factories\PropertyDeletionRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDeletionRequest extends Model
{
    /** @use HasFactory<PropertyDeletionRequestFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_property_id',
        'property_code',
        'property_title',
        'requested_by',
        'reviewed_by',
        'status',
        'reason',
        'review_note',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(PostProperty::class, 'post_property_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_PENDING;
    }

    public function statusBadgeClass(): string
    {
        return match (strtolower((string) $this->status)) {
            self::STATUS_APPROVED => 'badge-approved',
            self::STATUS_REJECTED => 'badge-rejected',
            default => 'badge-pending',
        };
    }
}
