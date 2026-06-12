<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Content\ServiceBindingResolver;
use App\Services\Import\ImportSupport;
use App\Services\Public\CatalogLineIconResolver;
use App\Services\Public\PublicDisplayNameResolver;
use App\Services\Public\ServiceCardImageResolver;

class ServiceInternalLinkingEngine
{
    public function __construct(
        private readonly ServicePublicUrlBuilder $urlBuilder,
        private readonly ServiceBindingResolver $serviceBinding,
        private readonly PublicDisplayNameResolver $displayNames,
        private readonly CatalogLineIconResolver $iconResolver,
        private readonly ServiceCardImageResolver $serviceImages,
    ) {}

    /**
     * @return array{related_services: list<array{code: string, title: string, url: string}>, related_locations: list<array{title: string, url: string, location_slug: string}>, related_pages: list<array{title: string, url: string}>}
     */
    public function build(Service $service): array
    {
        $service->loadMissing(['pincodes', 'locationPages.pincode', 'categories']);

        $relatedServices = $this->resolveRelatedServices($service);
        $relatedLocations = $this->resolveRelatedLocations($service);

        $relatedPages = [
            ['title' => __('Services catalog'), 'url' => url('/services-catalog')],
            ['title' => __('Locations'), 'url' => url('/locations')],
        ];

        $categoryCodes = $this->customList($service, 'related_category_codes');
        if ($categoryCodes !== []) {
            $relatedPages = array_merge(
                ServiceCategory::query()
                    ->active()
                    ->whereIn('code', array_map(fn ($c) => ServiceCategory::normalizeCode($c), $categoryCodes))
                    ->get(['name', 'code'])
                    ->map(fn (ServiceCategory $c): array => [
                        'title' => $this->displayNames->categoryHeadline($c),
                        'url' => $c->publicUrl(),
                    ])
                    ->values()
                    ->all(),
                $relatedPages
            );
        }

        return [
            'related_services' => $relatedServices,
            'related_locations' => $relatedLocations,
            'related_pages' => $relatedPages,
        ];
    }

    /**
     * @return list<array{code: string, title: string, url: string}>
     */
    private function resolveRelatedServices(Service $service): array
    {
        $codes = $this->customList($service, 'related_service_codes');
        if ($codes !== []) {
            $resolved = collect();
            foreach ($codes as $code) {
                $related = $this->serviceBinding->resolveForBlock($code);
                if ($related !== null && $related->isListedPublicly() && $related->id !== $service->id) {
                    $resolved->push($related);
                }
            }

            if ($resolved->isNotEmpty()) {
                return $resolved
                    ->unique('id')
                    ->map(fn (Service $s): array => $this->serviceLink($s))
                    ->values()
                    ->all();
            }
        }

        $serviceLimit = (int) config('services_master.internal_links.related_services_limit', 4);

        return Service::query()
            ->publicListing()
            ->whereKeyNot($service->id)
            ->when(
                $service->categories->isNotEmpty(),
                fn ($q) => $q->inCategories($service->categories->pluck('id')->all())
            )
            ->orderBy('sort_order')
            ->limit($serviceLimit)
            ->with('seo')
            ->get()
            ->map(fn (Service $s): array => $this->serviceLink($s))
            ->values()
            ->all();
    }

    /**
     * @return array{code: string, title: string, url: string, summary: ?string, line_icon: string}
     */
    private function serviceLink(Service $service): array
    {
        return [
            'code' => $service->service_code,
            'title' => $this->displayNames->serviceHeadline($service),
            'url' => $this->urlBuilder->serviceUrl($service),
            'summary' => $this->displayNames->serviceCardSummary($service),
            'line_icon' => $this->iconResolver->forService($service),
            'image_url' => $this->serviceImages->urlFor($service),
        ];
    }

    /**
     * @return list<array{title: string, url: string, location_slug: string}>
     */
    private function resolveRelatedLocations(Service $service): array
    {
        $pincodes = $this->customList($service, 'related_location_pincode');
        $locationLimit = (int) config('services_master.internal_links.related_locations_limit', 6);

        $query = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->with(['pincode', 'service', 'page']);

        if ($pincodes !== []) {
            $query->whereHas('pincode', fn ($q) => $q->whereIn('pincode', $pincodes));
        }

        return $query
            ->limit($locationLimit * 3)
            ->get()
            ->filter(fn (ServiceLocationPage $row): bool => $row->isPubliclyIndexable())
            ->take($locationLimit)
            ->map(function (ServiceLocationPage $row): array {
                $row->loadMissing(['service', 'pincode']);

                return [
                    'title' => app(ServiceLocationPageProvisioner::class)->locationTitle($row->service, $row->pincode),
                    'url' => $row->publicUrl(),
                    'location_slug' => (string) $row->location_slug,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function customList(Service $service, string $key): array
    {
        $custom = is_array($service->custom_fields) ? $service->custom_fields : [];
        $raw = $custom[$key] ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return ImportSupport::parseList($raw);
    }

    public function persist(Service $service): array
    {
        $links = $this->build($service);
        $service->forceFill(['internal_links_snapshot' => $links])->saveQuietly();

        return $links;
    }
}
