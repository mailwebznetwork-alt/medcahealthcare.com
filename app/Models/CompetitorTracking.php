<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorTracking extends Model
{
    protected $table = 'competitor_trackings';

    protected $fillable = [
        'competitor_keyword_id',
        'clicks',
        'impressions',
        'position',
        'recorded_date',
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'clicks' => 'integer',
        'impressions' => 'integer',
        'position' => 'integer',
    ];

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(CompetitorKeyword::class, 'competitor_keyword_id');
    }
}
