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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'search_volume' => 'integer',
            'difficulty' => 'integer',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(CompetitorTracking::class, 'competitor_keyword_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CompetitorLead::class, 'competitor_keyword_id');
    }

    public function conversionRate(): float
    {
        $clicks = (int) $this->trackings()->sum('clicks');
        if ($clicks === 0) {
            return 0.0;
        }

        $conversions = (int) $this->leads()
            ->where('status', 'converted')
            ->count();

        return round($conversions / $clicks, 4);
    }
}
