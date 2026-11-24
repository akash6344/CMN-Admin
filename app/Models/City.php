<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['state_id', 'city', 'status'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
