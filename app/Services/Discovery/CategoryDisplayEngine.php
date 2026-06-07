<?php

namespace App\Services\Discovery;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;

/**
 * Category → Service → Sub Service display for surfaces (not random).
 */
class CategoryDisplayEngine
{
    public function __construct(
        private readonly FeaturedContentEngine $featured,
        private readonly TopRatedEngine $topRated,
    ) {}

    /**
     * @param  list<int>  $categoryIds
     * @return array{categories: \Illuminate\Support\Collection, services: \Illuminate\Support\Collection, sub_services: \Illuminate\Support\Collection, featured_services: \Illuminate\Support\Collection, top_rated_services: \Illuminate\Support\Collection}
     */
    public function forSurface(string $surface, ?string $pincode = null, array $categoryIds = []): array
    {
        $categories = $this->resolveCategories($surface, $categoryIds);
        $categoryIdList = $categories->pluck('id')->all();

        $services = $this->resolveServices($surface, $pincode, $categoryIdList);
        $subServices = $this->resolveSubServices($services->pluck('id')->all());

        return [
            'categories' => $categories,
            'services' => $services,
            'sub_services' => $subServices,
            'featured_services' => $this->featured->featuredServices($surface, $pincode, $categoryIdList[0] ?? null),
            'top_rated_services' => $this->topRated->topRatedServices($categoryIdList[0] ?? null, $pincode),
        ];
    }

    /**
     * @param  list<int>  $categoryIds
     * @return \Illuminate\Support\Collection<int, ServiceCategory>
     */
    private function resolveCategories(string $surface, array $categoryIds): \Illuminate\Support\Collection
    {
        $query = ServiceCategory::query()->active()->ordered();

        if ($categoryIds !== []) {
            $query->whereIn('id', $categoryIds);
        } else {
            $query = match ($surface) {
                'homepage' => $query->where('show_on_homepage', true),
                'about' => $query->where('show_on_about', true),
                'contact' => $query->where('show_on_contact', true),
                default => $query,
            };
        }

        return $query
            ->limit((int) config('phase2_discovery.display.homepage_category_limit', 6))
            ->get();
    }

    /**
     * @param  list<int>  $categoryIds
     * @return \Illuminate\Support\Collection<int, Service>
     */
    private function resolveServices(string $surface, ?string $pincode, array $categoryIds): \Illuminate\Support\Collection
    {
        if ($categoryIds === []) {
            return collect();
        }

        $query = Service::query()
            ->publicListing()
            ->inCategories($categoryIds)
            ->with(['seo', 'categories', 'subServices' => fn ($q) => $q->publicListing()]);

        if ($pincode !== null && $pincode !== '') {
            $query->forPincode($pincode);
        }

        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit((int) config('phase2_discovery.display.homepage_service_limit', 8))
            ->get();
    }

    /**
     * @param  list<int>  $serviceIds
     * @return \Illuminate\Support\Collection<int, SubService>
     */
    private function resolveSubServices(array $serviceIds): \Illuminate\Support\Collection
    {
        if ($serviceIds === []) {
            return collect();
        }

        return SubService::query()
            ->publicListing()
            ->whereIn('service_id', $serviceIds)
            ->ordered()
            ->with('service:id,service_code,title')
            ->get();
    }
}
