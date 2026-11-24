<?php

namespace App\Models\Dashboard;

use App\Support\WebsiteSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * A document on a listing (website documents table).
 * Live website: property_id, document_name, document_category, verification_status, file_path (agreements, offer letters …).
 * Local CMN-Dashboard copy: post_property_id, title, status.
 */
class ListingDocument extends Model
{
    protected $connection = 'dashboard';

    protected $table = 'documents';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // the live table soft-deletes documents
        static::addGlobalScope('not_deleted', fn ($q) => WebsiteSchema::has('documents', 'deleted_at') ? $q->whereNull('documents.deleted_at') : $q);
    }

    /** Column that points at post_properties.id. */
    public static function listingKey(): string
    {
        return WebsiteSchema::has('documents', 'property_id') ? 'property_id' : 'post_property_id';
    }

    public function displayName(): string
    {
        $name = $this->document_name ?? $this->title ?? null;

        return trim((string) $name) !== '' ? (string) $name : 'Document';
    }

    public function category(): ?string
    {
        return filled($this->document_category ?? null) ? (string) $this->document_category : null;
    }

    public function displayStatus(): string
    {
        $s = $this->verification_status ?? $this->status ?? null;

        return ucfirst(str_replace('_', ' ', strtolower(trim((string) $s)) ?: 'uploaded'));
    }
}
