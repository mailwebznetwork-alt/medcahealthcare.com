<?php

namespace App\Services\Discovery;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Governance\VisibilityGovernanceService;

class FeaturedContentEngine
{
    public function __construct(
        private readonly VisibilityGovernanceService $visibility,
        private readonly ServiceDiscoveryVisibility $discoveryVisibility,
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, ServiceCategory>
     */
    public function featuredCategories(string $surface = 'homepage', ?string $pincode = null): \Illuminate\Support\Collection
    {
        $query = $this->visibility->scopeFeaturedCategories(ServiceCategory::query()->with(['seo']));

        return match ($surface) {
            'homepage' => $query->where('show_on_homepage', true)->ordered()->get(),
            'about' => $query->where('show_on_about', true)->ordered()->get(),
            'contact' => $query->where('show_on_contact', true)->ordered()->get(),
            default => $query->ordered()->limit((int) config('phase2_discovery.display.featured_limit', 6))->get(),
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function featuredServices(string $surface = 'homepage', ?string $pincode = null, ?int $categoryId = null): \Illuminate\Support\Collection
    {
        $query = $this->visibility->scopeFeaturedServices(Service::query()->with(['seo', 'categories']));

        $query = match ($surface) {
            'homepage' => $query->where('show_on_homepage', true),
            'about' => $query->where('show_on_about', true),
            'contact' => $query->where('show_on_contact', true),
            'category', 'category_pages' => $query,
            'location', 'location_pages' => $query,
            default => $query,
        };

        if ($categoryId !== null) {
            $query->inCategories([$categoryId]);
        }

        if ($pincode !== null && $pincode !== '') {
            $query->forPincode($pincode);
        }

        $limit = (int) config('phase2_discovery.display.featured_limit', 6);
        $discoverySurface = in_array($surface, ['category', 'category_pages'], true) ? 'category'
            : (in_array($surface, ['location', 'location_pages'], true) ? 'location' : $surface);

        return $query
            ->get()
            ->filter(fn (Service $service): bool => $this->discoveryVisibility->allowsOnSurface($service, $discoverySurface))
            ->sortByDesc(fn (Service $service): int => $this->discoveryVisibility->displayPriority($service))
            ->take($limit)
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, SubService>
     */
    public function featuredSubServices(?int $serviceId = null, string $surface = 'homepage'): \Illuminate\Support\Collection
    {
        $query = $this->visibility->scopeFeaturedSubServices(SubService::query()->with(['service', 'seo']));

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
        }

        $query = match ($surface) {
            'homepage' => $query->where('show_on_homepage', true),
            'about' => $query->where('show_on_about', true),
            'contact' => $query->where('show_on_contact', true),
            default => $query,
        };

        return $query->ordered()->limit((int) config('phase2_discovery.display.featured_limit', 6))->get();
    }
}
