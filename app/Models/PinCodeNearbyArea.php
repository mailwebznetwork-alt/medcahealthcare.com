<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinCodeNearbyArea extends Model
{
    protected $fillable = [
        'pincode_id',
        'area_name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function pincode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class, 'pincode_id');
    }
}
