<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorKeyword extends Model
{
    protected $fillable = [
        'competitor_id',
        'keyword',
        'intent_type',
        'search_volume',
        'difficulty',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(CompetitorTracking::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CompetitorLead::class);
    }

    public function conversionRate(): float
    {
        $clicks = $this->trackings()->sum('clicks');

        if ($clicks === 0) {
            return 0.0;
        }

        $conversions = $this->leads()->count();

        return round(($conversions / $clicks) * 100, 2);
    }
}
