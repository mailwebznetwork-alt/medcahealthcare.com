<?php

namespace App\Observers;

use App\Jobs\AnalyzeHijackOpportunityJob;
use App\Models\CompetitorTracking;
use App\Models\SiteKeywordRanking;
use App\Services\Growth\CompetitorComparisonService;

class CompetitorTrackingObserver
{
    public function created(CompetitorTracking $tracking): void
    {
        $keywordId = (int) $tracking->competitor_keyword_id;

        $previous = CompetitorTracking::query()
            ->where('competitor_keyword_id', $keywordId)
            ->where('id', '!=', $tracking->id)
            ->orderByDesc('recorded_date')
            ->orderByDesc('id')
            ->first();

        $competitorImproved = $tracking->position !== null
            && $previous?->position !== null
            && (int) $tracking->position < (int) $previous->position;

        $firstSnapshotWithGap = $previous === null
            && $tracking->position !== null
            && $this->competitorOutranksUs($tracking);

        if (! $competitorImproved && ! $firstSnapshotWithGap) {
            return;
        }

        $this->evaluateAndDispatch($keywordId);
    }

    private function competitorOutranksUs(CompetitorTracking $tracking): bool
    {
        $keyword = $tracking->keyword()->first();
        if ($keyword === null || ! $keyword->isHighIntent()) {
            return false;
        }

        $ourPosition = SiteKeywordRanking::latestPositionForKeyword($keyword->keyword);

        return $ourPosition !== null
            && $tracking->position !== null
            && (int) $tracking->position < $ourPosition;
    }

    private function evaluateAndDispatch(int $competitorKeywordId): void
    {
        $service = app(CompetitorComparisonService::class);
        $service->recalculateHijackPriorities($competitorKeywordId);

        $priority = $service->hijackPriorityForKeyword($competitorKeywordId);
        if ($priority !== null && $priority >= 1) {
            AnalyzeHijackOpportunityJob::dispatch($competitorKeywordId);
        }
    }
}
