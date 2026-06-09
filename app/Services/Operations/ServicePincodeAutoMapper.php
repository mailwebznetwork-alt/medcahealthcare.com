<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;
use App\Services\Governance\MappingProtectionService;
use App\Services\Seo\LocalityContextResolver;

/**
 * Links eligible pincodes to published public services and provisions location pages.
 */
final class ServicePincodeAutoMapper
{
    public function __construct(
        private readonly ServiceMasterOrchestrator $orchestrator,
        private readonly MappingProtectionService $mappingProtection,
        private readonly LocalityContextResolver $locality,
    ) {}

    /**
     * @return array{
     *     services_eligible: int,
     *     pincodes_eligible: int,
     *     service_codes: list<string>,
     *     message: string
     * }
     */
    public function estimate(?string $onlyServiceCode = null): array
    {
        [$pinIds, $services] = $this->resolveTargets($onlyServiceCode);

        if ($pinIds === []) {
            return [
                'services_eligible' => 0,
                'pincodes_eligible' => 0,
                'service_codes' => [],
                'message' => __('No eligible pincodes found for auto-mapping.'),
            ];
        }

        if ($services->isEmpty()) {
            return [
                'services_eligible' => 0,
                'pincodes_eligible' => count($pinIds),
                'service_codes' => [],
                'message' => __('No published public services found for auto-mapping.'),
            ];
        }

        return [
            'services_eligible' => $services->count(),
            'pincodes_eligible' => count($pinIds),
            'service_codes' => $services->pluck('service_code')->all(),
            'message' => __('Ready to auto-map :services service(s) across :pincodes eligible pincode(s).', [
                'services' => $services->count(),
                'pincodes' => count($pinIds),
            ]),
        ];
    }

    /**
     * @return array{
     *     mapped: bool,
     *     services_processed: int,
     *     pincodes_eligible: int,
     *     message: string
     * }
     */
    public function map(?string $onlyServiceCode = null, bool $provisionPages = true): array
    {
        [$pinIds, $services] = $this->resolveTargets($onlyServiceCode);

        if ($pinIds === []) {
            return [
                'mapped' => false,
                'services_processed' => 0,
                'pincodes_eligible' => 0,
                'message' => __('No eligible pincodes found for auto-mapping.'),
            ];
        }

        if ($services->isEmpty()) {
            return [
                'mapped' => false,
                'services_processed' => 0,
                'pincodes_eligible' => count($pinIds),
                'message' => __('No published public services found for auto-mapping.'),
            ];
        }

        $processed = 0;

        foreach ($services as $service) {
            $attachable = $this->mappingProtection->filterAttachablePinIds($service, $pinIds, 'sync');
            if ($attachable === []) {
                continue;
            }

            $service->pincodes()->syncWithoutDetaching($attachable);
            if ($provisionPages) {
                $this->orchestrator->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));
            }
            $processed++;
        }

        return [
            'mapped' => $processed > 0,
            'services_processed' => $processed,
            'pincodes_eligible' => count($pinIds),
            'message' => $processed > 0
                ? __('Auto-mapped :services service(s) across :pincodes eligible pincode(s).', [
                    'services' => $processed,
                    'pincodes' => count($pinIds),
                ])
                : __('No new service–pincode mappings were required.'),
        ];
    }

    /**
     * @return array{0: list<int>, 1: \Illuminate\Support\Collection<int, Service>}
     */
    private function resolveTargets(?string $onlyServiceCode): array
    {
        $config = config('services_master.pincode_expansion', []);
        $cityFilter = (string) ($config['city_filter'] ?? $this->locality->primaryCity() ?? '');
        $requireServiceable = (bool) ($config['require_serviceable'] ?? true);
        $requireActive = (bool) ($config['require_active'] ?? true);

        $pinQuery = PinCode::query();
        if ($requireActive) {
            $pinQuery->where('is_active', true);
        }
        if ($requireServiceable) {
            $pinQuery->where('is_serviceable', true);
        }
        if ($cityFilter !== '') {
            $pinQuery->where('city', 'like', '%'.$cityFilter.'%');
        }

        $pinIds = $pinQuery->pluck('id')->all();

        $serviceQuery = Service::query()->publicListing();
        if ($onlyServiceCode !== null && $onlyServiceCode !== '') {
            $serviceQuery->where('service_code', $onlyServiceCode);
        }

        return [$pinIds, $serviceQuery->get()];
    }
}
