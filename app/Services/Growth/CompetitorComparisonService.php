<?php

namespace App\Services\Growth;

use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorTracking;
use App\Models\SiteKeywordRanking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompetitorComparisonService
{
    /**
     * Recalculate stored priorities, then return the current opportunity set.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function identifyHighValueOpportunities(?int $competitorKeywordId = null): Collection
    {
        $this->recalculateHijackPriorities($competitorKeywordId);

        return $this->listHighValueOpportunities($competitorKeywordId);
    }

    /**
     * Read-only listing of keywords with a stored hijack priority (no DB writes).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listHighValueOpportunities(?int $competitorKeywordId = null): Collection
    {
        $ourRankings = $this->loadOurRankingIndex();

        $query = CompetitorKeyword::query()
            ->with('competitor:id,name')
            ->where('is_active', true)
            ->whereNotNull('hijack_priority')
            ->where('hijack_priority', '>=', 1);

        if ($competitorKeywordId !== null) {
            $query->whereKey($competitorKeywordId);
        }

        $keywords = $query->orderByDesc('hijack_priority')->get();
        if ($keywords->isEmpty()) {
            return collect();
        }

        $latestPositions = CompetitorTracking::latestPositionsByKeywordIds(
            $keywords->pluck('id')->all()
        );

        return $keywords
            ->map(function (CompetitorKeyword $keyword) use ($ourRankings, $latestPositions): ?array {
                $competitorPosition = $latestPositions->get($keyword->id);
                $normalized = SiteKeywordRanking::normalizeKeyword($keyword->keyword);
                $ourPosition = $ourRankings->get($normalized);

                if ($competitorPosition === null || $ourPosition === null) {
                    return null;
                }

                return [
                    'competitor_keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'competitor_id' => (int) $keyword->competitor_id,
                    'competitor_name' => $keyword->competitor?->name,
                    'intent_type' => (string) $keyword->intent_type,
                    'our_position' => $ourPosition,
                    'competitor_position' => (int) $competitorPosition,
                    'position_gap' => $ourPosition - (int) $competitorPosition,
                    'hijack_priority' => (int) $keyword->hijack_priority,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Persist hijack_priority scores for high-intent SERP gaps.
     */
    public function recalculateHijackPriorities(?int $competitorKeywordId = null): void
    {
        $ourRankings = $this->loadOurRankingIndex();

        $query = CompetitorKeyword::query()
            ->with('competitor:id,name')
            ->where('is_active', true);

        if ($competitorKeywordId !== null) {
            $query->whereKey($competitorKeywordId);
        }

        $query->orderBy('id')->chunkById(100, function ($keywords) use ($ourRankings): void {
            $latestPositions = CompetitorTracking::latestPositionsByKeywordIds(
                $keywords->pluck('id')->all()
            );

            foreach ($keywords as $keyword) {
                $this->evaluateAndPersistKeywordOpportunity($keyword, $ourRankings, $latestPositions);
            }
        });
    }

    public function hijackPriorityForKeyword(int $competitorKeywordId): ?int
    {
        $priority = CompetitorKeyword::query()->whereKey($competitorKeywordId)->value('hijack_priority');

        return $priority !== null ? (int) $priority : null;
    }

    /**
     * @param  Collection<string, int>  $ourRankings
     * @param  Collection<int, int>  $latestPositions
     */
    private function evaluateAndPersistKeywordOpportunity(
        CompetitorKeyword $keyword,
        Collection $ourRankings,
        Collection $latestPositions,
    ): ?array {
        if (! $keyword->isHighIntent()) {
            if ($keyword->hijack_priority !== null) {
                $keyword->forceFill(['hijack_priority' => null])->save();
            }

            return null;
        }

        $competitorPosition = $latestPositions->get($keyword->id);
        $normalized = SiteKeywordRanking::normalizeKeyword($keyword->keyword);
        $ourPosition = $ourRankings->get($normalized);

        if ($competitorPosition === null || $ourPosition === null || (int) $competitorPosition >= $ourPosition) {
            if ($keyword->hijack_priority !== null) {
                $keyword->forceFill(['hijack_priority' => null])->save();
            }

            return null;
        }

        $priority = $this->calculateHijackPriority((int) $competitorPosition, $ourPosition, $keyword);
        $keyword->forceFill(['hijack_priority' => $priority])->save();

        return [
            'competitor_keyword_id' => $keyword->id,
            'hijack_priority' => $priority,
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private function loadOurRankingIndex(): Collection
    {
        if (! Schema::hasTable('site_keyword_rankings')) {
            return collect();
        }

        return SiteKeywordRanking::query()
            ->orderByDesc('recorded_date')
            ->orderByDesc('id')
            ->get(['keyword', 'position'])
            ->unique(fn (SiteKeywordRanking $row) => SiteKeywordRanking::normalizeKeyword($row->keyword))
            ->mapWithKeys(fn (SiteKeywordRanking $row) => [
                SiteKeywordRanking::normalizeKeyword($row->keyword) => (int) $row->position,
            ]);
    }

    private function calculateHijackPriority(int $competitorPosition, int $ourPosition, CompetitorKeyword $keyword): int
    {
        $gapScore = min(10, max(1, $ourPosition - $competitorPosition));

        $volumeBoost = 0;
        if ($keyword->search_volume !== null && $keyword->search_volume >= 1000) {
            $volumeBoost = 2;
        } elseif ($keyword->search_volume !== null && $keyword->search_volume >= 300) {
            $volumeBoost = 1;
        }

        $difficultyBoost = 0;
        if ($keyword->difficulty !== null && $keyword->difficulty <= 40) {
            $difficultyBoost = 1;
        }

        $intentBoost = strtolower((string) $keyword->intent_type) === 'local' ? 1 : 0;

        return max(1, min(10, $gapScore + $volumeBoost + $difficultyBoost + $intentBoost));
    }

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
