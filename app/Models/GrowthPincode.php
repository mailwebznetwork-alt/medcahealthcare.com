<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthPincode extends Model
{
    protected $table = 'pincodes';

    protected $fillable = [
        'business_profile_id',
        'geo_location_id',
        'pincode',
        'serviceable',
        'landing_page',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'serviceable' => 'boolean',
        ];
    }

    public function geoLocation(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class);
    }
}
