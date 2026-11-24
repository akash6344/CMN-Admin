<?php

namespace App\Models\Dashboard;

use App\Models\PostProperty;

/** An owner/agent listing on the website — same as PostProperty, kept for the review relation. */
class Listing extends PostProperty
{
    protected $guarded = ['id'];
}
