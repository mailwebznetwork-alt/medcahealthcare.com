<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Competitor extends Model
{
    protected $fillable = [
        'name',
        'website',
        'is_active',
        'is_intercept_target',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_intercept_target' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(CompetitorKeyword::class);
    }

    public function trackings(): HasManyThrough
    {
        return $this->hasManyThrough(
            CompetitorTracking::class,
            CompetitorKeyword::class,
            'competitor_id',
            'competitor_keyword_id'
        );
    }

    public function leads(): HasManyThrough
    {
        return $this->hasManyThrough(CompetitorLead::class, CompetitorKeyword::class);
    }

    public function keywordsCount(): int
    {
        if (array_key_exists('keywords_count', $this->getAttributes())) {
            return (int) $this->keywords_count;
        }

        return $this->keywords()->count();
    }

    public function totalConversions(): int
    {
        if (array_key_exists('leads_count', $this->getAttributes())) {
            return (int) $this->leads_count;
        }

        return $this->leads()->count();
    }
}
