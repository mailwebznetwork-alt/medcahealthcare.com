<?php

namespace App\Services\Discovery;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;

class HealthcareDiscoveryEngine
{
    public function __construct(
        private readonly CategoryDisplayEngine $display,
        private readonly RelatedContentEngine $related,
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, ServiceCategory>
     */
    public function discoverCategories(?string $pincode = null): \Illuminate\Support\Collection
    {
        return ServiceCategory::query()
            ->active()
            ->ordered()
            ->with(['seo', 'services' => fn ($q) => $q->publicListing()->when(
                $pincode,
                fn ($sq) => $sq->forPincode($pincode)
            )])
            ->get()
            ->filter(fn (ServiceCategory $c): bool => $pincode === null || $c->services->isNotEmpty());
    }

    /**
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function discoverServices(?int $categoryId = null, ?string $pincode = null): \Illuminate\Support\Collection
    {
        $query = Service::query()->publicListing()->with(['seo', 'categories', 'subServices']);

        if ($categoryId !== null) {
            $query->inCategories([$categoryId]);
        }

        if ($pincode !== null && $pincode !== '') {
            $query->forPincode($pincode);
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, SubService>
     */
    public function discoverSubServices(?int $serviceId = null): \Illuminate\Support\Collection
    {
        $query = SubService::query()->publicListing()->with(['seo', 'service']);

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
        }

        return $query->ordered()->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ServiceLocationPage>
     */
    public function discoverLocations(?int $serviceId = null, ?string $pincode = null): \Illuminate\Support\Collection
    {
        $query = ServiceLocationPage::query()->with(['service', 'pincode', 'page']);

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
        }

        if ($pincode !== null && $pincode !== '') {
            $query->whereHas('pincode', fn ($q) => $q->where('pincode', $pincode));
        }

        return $query->get()->filter(fn (ServiceLocationPage $m): bool => $m->isPubliclyIndexable());
    }

    public function discoverPincode(string $pincode): ?PinCode
    {
        $normalized = preg_replace('/\D/', '', $pincode) ?? '';

        return PinCode::query()
            ->where('pincode', $normalized)
            ->where('is_active', true)
            ->with(['landmarks', 'hospitals', 'nearbyAreas', 'locationFaqs'])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function discoverForPincode(string $pincode): array
    {
        $pin = $this->discoverPincode($pincode);

        return [
            'pincode' => $pin?->pincode,
            'pin_record' => $pin,
            'categories' => $this->discoverCategories($pincode),
            'services' => $this->discoverServices(null, $pincode),
            'locations' => $this->discoverLocations(null, $pincode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryTree(int $categoryId, ?string $pincode = null): array
    {
        $category = ServiceCategory::query()->with(['seo', 'faqs', 'schema'])->find($categoryId);
        if ($category === null) {
            return [];
        }

        return [
            'category' => $category,
            'display' => $this->display->forSurface('category', $pincode, [$categoryId]),
            'related' => $this->related->buildForCategory($category, $pincode),
        ];
    }
}
