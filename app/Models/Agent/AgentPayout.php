<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Agent payout requests (CMN-Agent `payouts`). status: requested | processing | paid | rejected */
class AgentPayout extends Model
{
    protected $connection = 'agent';

    protected $table = 'payouts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'paid_at' => 'datetime', 'amount' => 'integer'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentUser::class, 'agent_id');
    }
}
