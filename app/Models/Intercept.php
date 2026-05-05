<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intercept extends Model
{
    protected $fillable = [
        'business_profile_id',
        'keyword',
        'competitor_id',
        'gap_type',
        'action',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'business_profile_id' => 'integer',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }
}
