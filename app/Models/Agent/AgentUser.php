<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;

/** Agent login accounts in the CMN-Agent database (users.id = agent_id). Read-only here. */
class AgentUser extends Model
{
    protected $connection = 'agent';

    protected $table = 'users';

    protected $guarded = ['*'];
}
