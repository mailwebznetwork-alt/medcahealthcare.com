<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_e164',
        'country_code',
        'street_address',
        'city',
        'region',
        'postal_code',
        'website',
        'address',
    ];
}
