<?php

namespace App\Models\Housing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A builder project (CMN-housing project_details). Read only in Admin: BRM reviews and publishes projects,
 * Admin shows their state in Properties → Builder projects (see PropertyIndex::projectState).
 */
class ProjectDetail extends Model
{
    protected $connection = 'housing';

    protected $table = 'project_details';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'highlights' => 'array',
            'media_preferences' => 'array',
            'possession_date' => 'date',
            'smart_bargain_enabled' => 'boolean',
        ];
    }

    public function unitConfigurations(): HasMany
    {
        return $this->hasMany(UnitConfiguration::class);
    }
}
