<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorLead;
use App\Models\CompetitorTracking;
use App\Models\PinCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cached competitor workbook rollup for Growth War Room (MarkOnMinds-style aggregates, MoM schema).
 */
final class WarRoomRollup
{
    public const string CACHE_KEY = 'markonminds:war_room:competitor_rollup:v1';

    public const int CACHE_TTL_SECONDS = 90;

    /**
     * @return array{
     *     total: int,
     *     active: int,
     *     interceptOn: int,
     *     avgTrust: float|null,
     *     keywordGaps: int,
     *     matrixRows: int,
     *     geoRefRules: int,
     *     attributionLeads: int
     * }
     */
    public static function build(): array
    {
        $base = Competitor::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_active = 1 then 1 else 0 end) as active_n')
            ->selectRaw('sum(case when is_active = 1 and is_intercept_target = 1 then 1 else 0 end) as intercept_on')
            ->first();

        $keywordGaps = Schema::hasTable('competitor_keywords')
            ? (int) CompetitorKeyword::query()->count()
            : 0;

        $matrixRows = Schema::hasTable('competitor_trackings')
            ? (int) CompetitorTracking::query()->count()
            : 0;

        $geoRefRules = Schema::hasTable('pin_codes')
            ? (int) PinCode::query()->count()
            : 0;

        $attributionLeads = Schema::hasTable('competitor_leads')
            ? (int) CompetitorLead::query()->count()
            : 0;

        return [
            'total' => (int) ($base->total ?? 0),
            'active' => (int) ($base->active_n ?? 0),
            'interceptOn' => (int) ($base->intercept_on ?? 0),
            'avgTrust' => null,
            'keywordGaps' => $keywordGaps,
            'matrixRows' => $matrixRows,
            'geoRefRules' => $geoRefRules,
            'attributionLeads' => $attributionLeads,
        ];
    }

    public static function cached(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            static fn (): array => self::build()
        );
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
