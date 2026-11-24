<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = ['state', 'status'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
