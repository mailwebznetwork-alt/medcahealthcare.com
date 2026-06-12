<?php

namespace App\Services\Discovery;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Operations\ServiceInternalLinkingEngine;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Operations\ServicePublicUrlBuilder;
use App\Services\Public\CatalogLineIconResolver;
use App\Services\Public\CategoryCardImageResolver;
use App\Services\Public\PublicDisplayNameResolver;
use App\Services\Public\ServiceCardImageResolver;

class RelatedContentEngine
{
    public function __construct(
        private readonly ServiceInternalLinkingEngine $serviceLinks,
        private readonly ServicePublicUrlBuilder $urlBuilder,
        private readonly PublicDisplayNameResolver $displayNames,
        private readonly CatalogLineIconResolver $iconResolver,
        private readonly CategoryCardImageResolver $categoryImages,
        private readonly ServiceCardImageResolver $serviceImages,
    ) {}

    /**
     * @return array{related_categories: list<array<string, mixed>>, related_services: list<array<string, mixed>>, related_sub_services: list<array<string, mixed>>, related_locations: list<array<string, mixed>>, related_pincodes: list<array<string, mixed>>}
     */
    public function buildForCategory(ServiceCategory $category, ?string $pincode = null): array
    {
        $category->loadMissing(['parent', 'children']);
        $limit = (int) config('phase2_discovery.display.related_categories_limit', 4);

        $relatedCategories = ServiceCategory::query()
            ->active()
            ->where('id', '!=', $category->id)
            ->when(
                $category->parent_id !== null,
                fn ($q) => $q->where('parent_id', $category->parent_id),
                fn ($q) => $q->whereNull('parent_id')
            )
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn (ServiceCategory $c): array => $this->categoryLink($c))
            ->values()
            ->all();

        $servicesQuery = Service::query()
            ->publicListing()
            ->whereHas('categories', fn ($q) => $q->where('service_categories.id', $category->id));

        if ($pincode !== null && $pincode !== '') {
            $servicesQuery->forPincode($pincode);
        }

        $relatedServices = $servicesQuery
            ->limit((int) config('phase2_discovery.display.homepage_service_limit', 8))
            ->with('seo')
            ->get()
            ->map(fn (Service $s): array => $this->serviceLink($s))
            ->values()
            ->all();

        $relatedSubServices = SubService::query()
            ->publicListing()
            ->whereHas('service.categories', fn ($q) => $q->where('service_categories.id', $category->id))
            ->limit((int) config('phase2_discovery.display.related_sub_services_limit', 8))
            ->with(['service:id,service_code,title', 'seo'])
            ->get()
            ->map(fn (SubService $sub): array => $this->subServiceLink($sub))
            ->values()
            ->all();

        return [
            'related_categories' => $relatedCategories,
            'related_services' => $relatedServices,
            'related_sub_services' => $relatedSubServices,
            'related_locations' => [],
            'related_pincodes' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForService(Service $service): array
    {
        $base = $this->serviceLinks->build($service);
        $service->loadMissing(['subServices']);

        $subCodes = $this->workbookSubServiceCodes($service);
        $subQuery = $service->subServices->filter(fn (SubService $sub): bool => $sub->isListedPublicly());

        if ($subCodes !== []) {
            $subQuery = $subQuery->filter(
                fn (SubService $sub): bool => in_array($sub->sub_service_code, $subCodes, true)
            );
        }

        $base['related_sub_services'] = $subQuery
            ->take((int) config('phase2_discovery.display.related_sub_services_limit', 8))
            ->map(fn (SubService $sub): array => $this->subServiceLink($sub))
            ->values()
            ->all();

        $base['related_categories'] = $service->categories
            ->map(fn (ServiceCategory $c): array => $this->categoryLink($c))
            ->values()
            ->all();

        return $base;
    }

    /**
     * @return list<string>
     */
    private function workbookSubServiceCodes(Service $service): array
    {
        $custom = is_array($service->custom_fields) ? $service->custom_fields : [];
        $raw = $custom['related_sub_service_codes'] ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return \App\Services\Import\ImportSupport::parseList($raw);
    }

    public function persistCategory(ServiceCategory $category, ?string $pincode = null): array
    {
        $links = $this->buildForCategory($category, $pincode);
        $category->forceFill(['internal_links_snapshot' => $links])->saveQuietly();

        return $links;
    }

    public function persistSubService(SubService $sub): array
    {
        $sub->loadMissing('service');
        $service = $sub->service;
        $links = [
            'parent_service' => $service ? [
                'code' => $service->service_code,
                'title' => $service->title,
                'url' => $this->urlBuilder->serviceUrl($service),
                'summary' => $this->displayNames->serviceCardSummary($service),
                'line_icon' => $this->iconResolver->forService($service),
                'image_url' => $this->serviceImages->urlFor($service),
            ] : null,
            'related_services' => $service
                ? $this->serviceLinks->build($service)['related_services']
                : [],
        ];
        $sub->forceFill(['internal_links_snapshot' => $links])->saveQuietly();

        return $links;
    }

    /**
     * @return array{code: string, name: string, url: string, summary: ?string, line_icon: string}
     */
    private function categoryLink(ServiceCategory $category): array
    {
        return [
            'code' => $category->code,
            'name' => $category->name,
            'url' => $category->publicUrl(),
            'summary' => $this->displayNames->categoryCardSummary($category),
            'line_icon' => $this->iconResolver->forCategory($category),
            'image_url' => $this->categoryImages->urlFor($category),
        ];
    }

    /**
     * @return array{code: string, title: string, url: string, summary: ?string, line_icon: string}
     */
    private function serviceLink(Service $service): array
    {
        return [
            'code' => $service->service_code,
            'title' => $service->title,
            'url' => $this->urlBuilder->serviceUrl($service),
            'summary' => $this->displayNames->serviceCardSummary($service),
            'line_icon' => $this->iconResolver->forService($service),
            'image_url' => $this->serviceImages->urlFor($service),
        ];
    }

    /**
     * @return array{code: string, title: string, parent_code: ?string, url: string, summary: ?string, line_icon: string}
     */
    private function subServiceLink(SubService $sub): array
    {
        return [
            'code' => $sub->sub_service_code,
            'title' => $sub->title,
            'parent_code' => $sub->service?->service_code,
            'url' => app(\App\Services\Discovery\Expansion\SeoExpansionEngine::class)->subServicePublicUrl($sub),
            'summary' => $this->displayNames->subServiceCardSummary($sub),
            'line_icon' => $this->iconResolver->forSubService($sub),
            'image_url' => $this->serviceImages->urlForSubService($sub),
        ];
    }
}
