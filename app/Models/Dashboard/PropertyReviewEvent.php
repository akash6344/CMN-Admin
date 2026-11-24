<?php

namespace App\Models\Dashboard;

use Illuminate\Database\Eloquent\Model;

class PropertyReviewEvent extends Model
{
    protected $connection = 'ops';   // CMN-Emp's DB (the review trail moved off the website DB)

    protected $guarded = ['id'];
}
