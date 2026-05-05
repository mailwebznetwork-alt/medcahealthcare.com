<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorLead extends Model
{
    protected $fillable = [
        'competitor_keyword_id',
        'source',
        'details',
        'status',
    ];

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(CompetitorKeyword::class, 'competitor_keyword_id');
    }
}
