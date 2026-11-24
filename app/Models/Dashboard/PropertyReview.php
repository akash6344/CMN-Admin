<?php

namespace App\Models\Dashboard;

use App\Support\PropertyIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Operations' review of an owner / agent listing (property_reviews, kind "listing", in CMN-Emp's DB — connection "ops"), written by CMN-Emp. The listing is in the website DB: no joins / whereHas across the two.
 * Admin takes over at "ready_for_admin" and either approves (goes live) or raises a query → "admin_query" (back to Operations).
 * Old kind "project" rows are history only: builder projects are reviewed by BRM, and Admin refuses to act on them.
 */
class PropertyReview extends Model
{
    protected $connection = 'ops';   // CMN-Emp's DB (the review trail moved off the website DB)

    protected $guarded = ['id'];

    /** labels shared with CMN-Emp ListingValidator */
    public const CHECKS = [
        'details' => 'Property details complete', 'contact' => 'Mobile number valid', 'location' => 'Location verified', 'price' => 'Price entered',
        'photos' => 'Cover photo uploaded', 'documents' => 'Documents uploaded', 'duplicate' => 'Duplicate check passed', 'market' => 'Price matches market trend',
    ];

    public const MANUAL = [
        'kyc' => 'Owner KYC verified', 'contact_called' => 'Contact confirmed by call', 'images_quality' => 'Images meet quality standards',
        'description_ok' => 'Description accurate', 'amenities' => 'Amenities verified', 'map_ok' => 'Map location verified',
    ];

    protected function casts(): array
    {
        return [
            'checks' => 'array', 'manual_checks' => 'array', 'flagged' => 'boolean',
            'checked_at' => 'datetime', 'submitted_to_admin_at' => 'datetime', 'admin_query_at' => 'datetime', 'decided_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'post_property_id');
    }

    public function isProject(): bool
    {
        return $this->kind === 'project';
    }

    public function subjectExists(): bool
    {
        return $this->listing !== null;
    }

    /**
     * What Admin needs to see about the listing.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $d = PropertyIndex::describe('listing', $this->listing);

        return $d + ['kind' => $d['kindLabel']];
    }

    /** @return array<string, string> */
    public function checkLabels(): array
    {
        return self::CHECKS;
    }

    /** @return array<string, string> */
    public function manualLabels(): array
    {
        return self::MANUAL;
    }

    public function events(): HasMany
    {
        return $this->hasMany(PropertyReviewEvent::class)->orderBy('id');
    }

    public function log(string $event, ?string $actor = null, ?string $note = null): void
    {
        $this->events()->create(['event' => $event, 'actor' => $actor, 'note' => $note]);
    }
}
