<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinCodeLandmark extends Model
{
    protected $fillable = [
        'pincode_id',
        'name',
        'landmark_type',
        'distance_km',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function pincode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class, 'pincode_id');
    }
}
