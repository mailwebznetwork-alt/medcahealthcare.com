<?php

namespace App\Services;

use App\Models\Competitor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompetitorComparisonService
{
    public function compareCompetitors(array $ids): Collection
    {
        return Competitor::whereIn('id', $ids)
            ->withCount(['keywords', 'leads'])
            ->withSum('trackings as total_clicks', 'clicks')
            ->get()
            ->map(function (Competitor $competitor) {
                $clicks = (int) $competitor->total_clicks;
                $conversions = $competitor->totalConversions();
                $rate = $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0.0;

                return [
                    'id' => $competitor->id,
                    'name' => $competitor->name,
                    'is_intercept_target' => $competitor->is_intercept_target,
                    'total_keywords' => $competitor->keywordsCount(),
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'conversion_rate' => $rate,
                ];
            });
    }

    public function getBestPerformer(): ?array
    {
        return $this->getPerformerByConversionOrder('desc');
    }

    public function getWorstPerformer(): ?array
    {
        return $this->getPerformerByConversionOrder('asc');
    }

    public function getKeywordOverlap(array $ids): Collection
    {
        return DB::table('competitor_keywords')
            ->select('keyword', DB::raw('COUNT(DISTINCT competitor_id) as competitor_count'))
            ->whereIn('competitor_id', $ids)
            ->groupBy('keyword')
            ->having('competitor_count', '>', 1)
            ->orderByDesc('competitor_count')
            ->get();
    }

    private function getPerformerByConversionOrder(string $direction): ?array
    {
        $competitor = Competitor::active()
            ->withCount('leads')
            ->orderBy('leads_count', $direction)
            ->first();

        if (! $competitor) {
            return null;
        }

        return [
            'id' => $competitor->id,
            'name' => $competitor->name,
            'conversions' => $competitor->leads_count,
        ];
    }
}
