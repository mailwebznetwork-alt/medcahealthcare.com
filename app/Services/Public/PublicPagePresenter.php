<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\Vacancy;
use App\Services\Discovery\CategoryDisplayEngine;
use App\Services\Discovery\HealthcareDiscoveryEngine;
use App\Services\UserLocationService;

class PublicPagePresenter
{
    public function __construct(
        private readonly UserLocationService $location,
        private readonly CategoryDisplayEngine $categoryDisplay,
        private readonly HealthcareDiscoveryEngine $discovery,
    ) {}

    /**
     * Blade variables injected into all blocks when rendering a CMS page.
     *
     * @return array<string, mixed>
     */
    public function variablesFor(Page $page): array
    {
        return match ($page->slug) {
            'home' => array_merge(
                $this->categoryDisplay->forSurface('homepage', $this->location->currentPincode()),
                [
                    'nearYouCategories' => $this->localizedCategories(limit: 6),
                    'nearYouPayload' => $this->nearYouPayload(),
                ]
            ),
            'careers' => [
                'vacancies' => Vacancy::query()->careersListing()->get(),
            ],
            'locations' => [
                'pinCodes' => PinCode::query()
                    ->where('is_active', true)
                    ->orderBy('city')
                    ->orderBy('pincode')
                    ->get(),
                'nearYouCategories' => $this->localizedCategories(limit: 0),
                'nearYouPayload' => $this->nearYouPayload(limit: 0),
            ],
            'services' => [
                'publishedServices' => $this->localizedServices(),
                'nearYouCategories' => $this->localizedCategories(limit: 6),
            ],
            default => [],
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function localizedServices(?string $pincode = null, int $limit = 0): \Illuminate\Support\Collection
    {
        $pincode ??= $this->location->currentPincode();
        if ($pincode === null) {
            return collect();
        }

        $query = Service::query()
            ->localizedListing($pincode)
            ->with(['seo', 'pincodes', 'categories', 'faqs']);

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ServiceCategory>
     */
    public function localizedCategories(?string $pincode = null, int $limit = 6): \Illuminate\Support\Collection
    {
        $pincode ??= $this->location->currentPincode();
        if ($pincode === null) {
            return collect();
        }

        $categories = $this->discovery->discoverCategories($pincode);

        return $limit > 0 ? $categories->take($limit) : $categories;
    }

    /**
     * @return array<string, mixed>
     */
    public function nearYouPayload(int $limit = 6): array
    {
        $pincode = $this->location->currentPincode();
        $record = $this->location->currentPinCodeRecord();

        return [
            'pincode' => $pincode,
            'pinCodeRecord' => $record,
            'categories' => $this->localizedCategories($pincode, $limit),
            'locationRequired' => $pincode === null,
        ];
    }

    /**
     * Blade variables for /services/{code} CMS detail pages.
     *
     * @return array<string, mixed>
     */
    public function variablesForServiceDetail(Service $service): array
    {
        $service->loadMissing(['seo', 'faqs', 'pincodes', 'subServices' => fn ($q) => $q->publicListing()]);

        return [
            'service' => $service,
            $service->bladeVariableName() => $service,
            'subServices' => $service->subServices,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function variablesForCategoryDetail(ServiceCategory $category, ?string $pincode = null): array
    {
        $category->loadMissing(['seo', 'faqs', 'schema']);
        $display = $this->categoryDisplay->forSurface('category', $pincode, [$category->id]);

        return array_merge($display, [
            'category' => $category,
            'serviceCategory' => $category,
            'categoryServices' => $display['services'],
            'internalLinks' => $category->internal_links_snapshot ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function variablesForSubServiceDetail(SubService $sub): array
    {
        $sub->loadMissing(['seo', 'faqs', 'service']);

        return [
            'subService' => $sub,
            'service' => $sub->service,
            'internalLinks' => $sub->internal_links_snapshot ?? [],
        ];
    }

    /**
     * Blade variables for /careers/{slug} when rendered via a CMS detail page.
     *
     * @return array<string, mixed>
     */
    public function variablesForVacancyDetail(Vacancy $vacancy): array
    {
        return [
            'vacancy' => $vacancy,
        ];
    }
}
