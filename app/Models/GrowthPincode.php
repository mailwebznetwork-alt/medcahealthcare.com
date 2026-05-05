<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

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

    protected static function booted(): void
    {
        static::creating(function (GrowthPincode $model): void {
            if (! Schema::hasColumn($model->getTable(), 'code')) {
                return;
            }

            if (filled($model->code)) {
                return;
            }

            if (filled($model->pincode)) {
                $model->code = $model->pincode;
            }
        });
    }

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
