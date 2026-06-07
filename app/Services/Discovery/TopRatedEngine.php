<?php

namespace App\Services\Discovery;

use App\Models\Review;
use App\Models\Service;
use App\Models\SubService;
use App\Services\Governance\VisibilityGovernanceService;

class TopRatedEngine
{
    public function __construct(
        private readonly VisibilityGovernanceService $visibility,
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function topRatedServices(?int $categoryId = null, ?string $pincode = null, int $limit = 0): \Illuminate\Support\Collection
    {
        $limit = $limit > 0 ? $limit : (int) config('phase2_discovery.display.top_rated_limit', 6);

        $query = $this->visibility->scopeTopRatedServices(Service::query()->with(['seo', 'categories']));

        if ($categoryId !== null) {
            $query->inCategories([$categoryId]);
        }

        if ($pincode !== null && $pincode !== '') {
            $query->forPincode($pincode);
        }

        return $query->orderByDesc('avg_rating_cache')->orderBy('sort_order')->limit($limit)->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, SubService>
     */
    public function topRatedSubServices(?int $serviceId = null, int $limit = 0): \Illuminate\Support\Collection
    {
        $limit = $limit > 0 ? $limit : (int) config('phase2_discovery.display.top_rated_limit', 6);

        $query = SubService::query()->publicListing()->where('is_top_rated', true)->with('service');

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
        }

        return $query->orderByDesc('avg_rating_cache')->ordered()->limit($limit)->get();
    }

    public function refreshServiceRatings(): int
    {
        $minReviews = (int) config('phase2_discovery.top_rated.min_reviews', 3);
        $minRating = (float) config('phase2_discovery.top_rated.min_rating', 4.5);
        $updated = 0;

        Service::query()->each(function (Service $service) use ($minReviews, $minRating, &$updated): void {
            $count = $service->approvedReviewsCount();
            $avg = $service->averageApprovedRating();
            $isTop = $count >= $minReviews && $avg !== null && $avg >= $minRating;

            $service->forceFill([
                'avg_rating_cache' => $avg,
                'is_top_rated' => $isTop,
            ])->saveQuietly();

            $updated++;
        });

        return $updated;
    }
}
