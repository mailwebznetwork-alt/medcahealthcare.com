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
        'hijack_priority',
    ];

    /** @var list<string> */
    public const HIGH_INTENT_TYPES = ['local', 'service', 'high_intent'];

    protected function casts(): array
    {
        return [
            'search_volume' => 'integer',
            'difficulty' => 'integer',
            'hijack_priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isHighIntent(): bool
    {
        return in_array(strtolower((string) $this->intent_type), self::HIGH_INTENT_TYPES, true);
    }

    public function latestPosition(): ?int
    {
        $position = CompetitorTracking::latestPositionsByKeywordIds([$this->id])->get($this->id);

        return $position !== null ? (int) $position : null;
    }

    public function isHijackOpportunity(): bool
    {
        return $this->hijack_priority !== null && (int) $this->hijack_priority >= 1;
    }

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
