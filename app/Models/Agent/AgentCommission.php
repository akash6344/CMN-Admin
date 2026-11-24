<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agent earnings ledger (CMN-Agent `commissions`). Amounts in whole rupees.
 * entry_type: commission | adjustment · status: pending | received
 */
class AgentCommission extends Model
{
    protected $connection = 'agent';

    protected $table = 'commissions';

    protected $guarded = [];

    public const DEAL_TYPES = ['sale' => 'Sale', 'rent' => 'Rent', 'bargain' => 'Smart Bargain', 'other' => 'Other'];

    protected function casts(): array
    {
        return ['earned_at' => 'datetime', 'received_at' => 'datetime', 'amount' => 'integer', 'deal_value' => 'integer'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentUser::class, 'agent_id');
    }
}
