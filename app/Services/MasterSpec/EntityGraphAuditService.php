<?php

namespace App\Services\MasterSpec;

use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;

class EntityGraphAuditService
{
    /**
     * @return array{
     *     orphan_services: int,
     *     orphan_sub_services: int,
     *     orphan_categories: int,
     *     services_without_pincodes: int,
     *     services_without_seo: int,
     *     location_pages_orphan: int,
     *     pincodes_without_zone: int,
     *     weak_relationships: list<array<string, mixed>>,
     *     missing_relationships: list<array<string, mixed>>
     * }
     */
    public function audit(): array
    {
        $orphanServices = Service::query()
            ->whereDoesntHave('categories')
            ->count();

        $orphanSubServices = SubService::query()
            ->whereDoesntHave('service')
            ->count();

        $orphanCategories = ServiceCategory::query()
            ->whereDoesntHave('services')
            ->whereNull('parent_id')
            ->count();

        $servicesWithoutPincodes = Service::query()
            ->whereDoesntHave('pincodes')
            ->count();

        $servicesWithoutSeo = Service::query()
            ->whereDoesntHave('seo')
            ->count();

        $locationOrphans = ServiceLocationPage::query()
            ->where(function ($q): void {
                $q->whereNull('page_id')
                    ->orWhereDoesntHave('service')
                    ->orWhereDoesntHave('pincode');
            })
            ->count();

        $pincodesWithoutZone = PinCode::query()
            ->whereNull('bangalore_zone_id')
            ->where('is_active', true)
            ->count();

        $weak = [];
        $missing = [];

        Service::query()
            ->withCount(['pincodes', 'subServices', 'faqs'])
            ->where('is_active', true)
            ->orderBy('service_code')
            ->limit(500)
            ->get()
            ->each(function (Service $service) use (&$weak, &$missing): void {
                if ($service->pincodes_count === 0) {
                    $missing[] = [
                        'type' => 'service',
                        'code' => $service->service_code,
                        'issue' => 'no_pincode_coverage',
                    ];
                }

                if ($service->faqs_count === 0) {
                    $weak[] = [
                        'type' => 'service',
                        'code' => $service->service_code,
                        'issue' => 'no_faqs',
                    ];
                }

                if ($service->pincodes_count > 0 && $service->pincodes_count < 3) {
                    $weak[] = [
                        'type' => 'service',
                        'code' => $service->service_code,
                        'issue' => 'thin_pincode_coverage',
                        'count' => $service->pincodes_count,
                    ];
                }
            });

        $registryOrphans = PageRegistry::query()
            ->whereDoesntHave('page')
            ->count();

        if ($registryOrphans > 0) {
            $missing[] = [
                'type' => 'page_registry',
                'issue' => 'registry_without_page',
                'count' => $registryOrphans,
            ];
        }

        return [
            'orphan_services' => $orphanServices,
            'orphan_sub_services' => $orphanSubServices,
            'orphan_categories' => $orphanCategories,
            'services_without_pincodes' => $servicesWithoutPincodes,
            'services_without_seo' => $servicesWithoutSeo,
            'location_pages_orphan' => $locationOrphans,
            'pincodes_without_zone' => $pincodesWithoutZone,
            'weak_relationships' => array_slice($weak, 0, 100),
            'missing_relationships' => array_slice($missing, 0, 100),
        ];
    }
}
