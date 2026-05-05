<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorTracking extends Model
{
    protected $fillable = [
        'competitor_keyword_id',
        'clicks',
        'impressions',
        'position',
        'recorded_date',
    ];

    protected function casts(): array
    {
        return [
            'recorded_date' => 'date',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(CompetitorKeyword::class, 'competitor_keyword_id');
    }
}
