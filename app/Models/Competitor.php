<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'website',
        'is_active',
        'is_intercept_target',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_intercept_target' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeInterceptTargets(Builder $query): void
    {
        $query->where('is_intercept_target', true);
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
        return $this->hasManyThrough(
            CompetitorLead::class,
            CompetitorKeyword::class,
            'competitor_id',
            'competitor_keyword_id'
        );
    }
}
