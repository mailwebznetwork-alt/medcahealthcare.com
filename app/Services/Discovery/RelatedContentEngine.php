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

class RelatedContentEngine
{
    public function __construct(
        private readonly ServiceInternalLinkingEngine $serviceLinks,
        private readonly ServicePublicUrlBuilder $urlBuilder,
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
            ->get(['id', 'name', 'code', 'slug'])
            ->map(fn (ServiceCategory $c): array => [
                'code' => $c->code,
                'name' => $c->name,
                'url' => $c->publicUrl(),
            ])
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
            ->get(['id', 'title', 'service_code'])
            ->map(fn (Service $s): array => [
                'code' => $s->service_code,
                'title' => $s->title,
                'url' => $this->urlBuilder->serviceUrl($s),
            ])
            ->values()
            ->all();

        $relatedSubServices = SubService::query()
            ->publicListing()
            ->whereHas('service.categories', fn ($q) => $q->where('service_categories.id', $category->id))
            ->limit((int) config('phase2_discovery.display.related_sub_services_limit', 8))
            ->with('service:id,service_code,title')
            ->get()
            ->map(fn (SubService $sub): array => [
                'code' => $sub->sub_service_code,
                'title' => $sub->title,
                'parent_code' => $sub->service?->service_code,
                'url' => app(\App\Services\Discovery\Expansion\SeoExpansionEngine::class)->subServicePublicUrl($sub),
            ])
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
            ->map(fn (SubService $sub): array => [
                'code' => $sub->sub_service_code,
                'title' => $sub->title,
                'url' => app(\App\Services\Discovery\Expansion\SeoExpansionEngine::class)->subServicePublicUrl($sub),
            ])
            ->values()
            ->all();

        $base['related_categories'] = $service->categories
            ->map(fn (ServiceCategory $c): array => [
                'code' => $c->code,
                'name' => $c->name,
                'url' => $c->publicUrl(),
            ])
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
            ] : null,
            'related_services' => $service
                ? $this->serviceLinks->build($service)['related_services']
                : [],
        ];
        $sub->forceFill(['internal_links_snapshot' => $links])->saveQuietly();

        return $links;
    }
}
