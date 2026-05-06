<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSlugRedirect extends Model
{
    protected $fillable = [
        'from_slug',
        'to_slug',
    ];
}
