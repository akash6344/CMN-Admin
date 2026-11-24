<?php

namespace App\Models\Housing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry in a ticket thread: description | reply | note (internal, builder never sees it) | event. */
class SupportTicketMessage extends Model
{
    protected $connection = 'housing';

    protected $guarded = ['id'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /** Builder-portal uploads live on the builder portal's public disk. */
    public function attachmentUrl(): ?string
    {
        $base = rtrim((string) config('services.housing.public_url', 'http://localhost:8091'), '/');

        return $this->attachment_path ? $base.'/storage/'.ltrim($this->attachment_path, '/') : null;
    }

    public function fromBuilder(): bool
    {
        return $this->author_type === 'builder';
    }
}
