<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'website',
        'address',
    ];
}
