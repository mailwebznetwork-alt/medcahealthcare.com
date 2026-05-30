<?php

namespace App\Observers;

use App\Jobs\AnalyzeHijackOpportunityJob;
use App\Models\CompetitorKeyword;
use App\Models\SiteKeywordRanking;
use App\Services\Growth\CompetitorComparisonService;

class SiteKeywordRankingObserver
{
    public function created(SiteKeywordRanking $ranking): void
    {
        $this->handleRankingChange($ranking);
    }

    public function updated(SiteKeywordRanking $ranking): void
    {
        if (! $ranking->wasChanged('position')) {
            return;
        }

        $this->handleRankingChange($ranking, (int) ($ranking->getOriginal('position') ?? 0));
    }

    private function handleRankingChange(SiteKeywordRanking $ranking, ?int $previousPosition = null): void
    {
        if ($ranking->position === null) {
            return;
        }

        if ($previousPosition === null) {
            $previous = SiteKeywordRanking::query()
                ->where('keyword', SiteKeywordRanking::normalizeKeyword($ranking->keyword))
                ->where('id', '!=', $ranking->id)
                ->orderByDesc('recorded_date')
                ->orderByDesc('id')
                ->value('position');

            $previousPosition = $previous !== null ? (int) $previous : null;
        }

        $ourRankingDropped = $previousPosition !== null
            && (int) $ranking->position > $previousPosition;

        if (! $ourRankingDropped) {
            return;
        }

        $normalized = SiteKeywordRanking::normalizeKeyword($ranking->keyword);
        $keywordIds = CompetitorKeyword::query()
            ->whereRaw('lower(trim(keyword)) = ?', [$normalized])
            ->pluck('id');

        $service = app(CompetitorComparisonService::class);

        foreach ($keywordIds as $keywordId) {
            $service->recalculateHijackPriorities((int) $keywordId);

            $priority = $service->hijackPriorityForKeyword((int) $keywordId);
            if ($priority !== null && $priority >= 1) {
                AnalyzeHijackOpportunityJob::dispatch((int) $keywordId);
            }
        }
    }
}
