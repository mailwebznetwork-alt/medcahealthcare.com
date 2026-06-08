<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Governance\DownstreamArtifactPurger;
use Illuminate\Support\Collection;

/**
 * Keeps service_pincodes pivot, location pages, indexability, and links synchronized.
 */
class ServiceLocationMatrixReconciler
{
    public function __construct(
        private readonly ServiceLocationPageProvisioner $locationProvisioner,
        private readonly InternalLinkRefreshDispatcher $linkDispatcher,
    ) {}

    /**
     * @return array{
     *   services_processed: int,
     *   pages_provisioned: int,
     *   pages_updated: int,
     *   pages_removed: int,
     *   pages_deactivated: int,
     *   indexable_count: int,
     *   sitemap_eligible: int,
     *   issues: list<string>
     * }
     */
    public function reconcile(?Service $onlyService = null): array
    {
        $report = [
            'services_processed' => 0,
            'pages_provisioned' => 0,
            'pages_updated' => 0,
            'pages_removed' => 0,
            'pages_deactivated' => 0,
            'indexable_count' => 0,
            'sitemap_eligible' => 0,
            'issues' => [],
        ];

        $services = $this->resolveServices($onlyService);

        foreach ($services as $service) {
            $report['services_processed']++;
            $service->loadMissing(['pincodes', 'categories', 'locationPages.pincode', 'locationPages.page']);

            $syncResult = $this->locationProvisioner->syncAllForService($service);
            $report['pages_provisioned'] += $syncResult['created'];
            $report['pages_updated'] += $syncResult['updated'];
            $report['pages_removed'] += $syncResult['removed'];

            foreach ($service->pincodes as $pin) {
                if (! ServiceLocationMatrixPivot::isActive($service, $pin)) {
                    $deactivated = $this->deactivateMapping($service->id, $pin->id);
                    if ($deactivated) {
                        $report['pages_deactivated']++;
                    }
                }
            }

            $this->linkDispatcher->dispatchForService($service->id);

            $mappings = ServiceLocationPage::query()
                ->where('service_id', $service->id)
                ->with(['page', 'service', 'pincode'])
                ->get();

            foreach ($mappings as $mapping) {
                if ($mapping->isPubliclyIndexable()) {
                    $report['indexable_count']++;
                    $report['sitemap_eligible']++;
                }
            }

            $pivotPinIds = $service->pincodes->pluck('id')->all();
            $orphanRows = ServiceLocationPage::query()
                ->where('service_id', $service->id)
                ->when($pivotPinIds !== [], fn ($q) => $q->whereNotIn('pincode_id', $pivotPinIds))
                ->with('page')
                ->get();

            foreach ($orphanRows as $orphan) {
                $this->locationProvisioner->removeMappingAndPage($orphan);
                $report['pages_removed']++;
                $report['issues'][] = "service:{$service->service_code} purged orphan mapping pin:{$orphan->pincode_id}";
            }
        }

        app(DownstreamArtifactPurger::class)->purgeRegistryOrphans();

        return $report;
    }

    /**
     * @return Collection<int, Service>
     */
    private function resolveServices(?Service $onlyService): Collection
    {
        if ($onlyService !== null) {
            return collect([$onlyService->fresh(['pincodes', 'categories'])]);
        }

        return Service::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function deactivateMapping(int $serviceId, int $pincodeId): bool
    {
        $mapping = ServiceLocationPage::query()
            ->where('service_id', $serviceId)
            ->where('pincode_id', $pincodeId)
            ->with('page')
            ->first();

        if ($mapping === null) {
            return false;
        }

        $mapping->forceFill(['is_indexable' => false])->saveQuietly();

        if ($mapping->page !== null) {
            $mapping->page->forceFill([
                'robots_meta' => 'noindex,follow',
                'is_active' => false,
            ])->saveQuietly();
        }

        return true;
    }
}
