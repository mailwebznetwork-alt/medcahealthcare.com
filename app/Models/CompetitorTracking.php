<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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

    /**
     * Latest SERP position per competitor keyword (one query).
     *
     * @param  list<int>  $keywordIds
     * @return Collection<int, int>
     */
    public static function latestPositionsByKeywordIds(array $keywordIds): Collection
    {
        if ($keywordIds === []) {
            return collect();
        }

        return static::query()
            ->select(['competitor_keyword_id', 'position'])
            ->whereIn('competitor_keyword_id', $keywordIds)
            ->whereIn('id', function ($query) use ($keywordIds): void {
                $query->selectRaw('MAX(id)')
                    ->from('competitor_trackings')
                    ->whereIn('competitor_keyword_id', $keywordIds)
                    ->groupBy('competitor_keyword_id');
            })
            ->pluck('position', 'competitor_keyword_id')
            ->map(fn ($position) => $position !== null ? (int) $position : null)
            ->filter(fn ($position) => $position !== null);
    }
}
