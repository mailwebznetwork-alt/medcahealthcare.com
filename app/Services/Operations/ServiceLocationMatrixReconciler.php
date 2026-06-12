<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Growth\SitemapRegenerationDispatcher;
use App\Services\Import\ImportSideEffectsGate;
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
     *   indexable_count: int,
     *   sitemap_eligible: int,
     *   issues: list<string>
     * }
     */
    public function reconcile(?Service $onlyService = null, bool $purgeCatalogOrphans = true, bool $refreshExisting = false): array
    {
        $report = [
            'services_processed' => 0,
            'pages_provisioned' => 0,
            'pages_updated' => 0,
            'pages_removed' => 0,
            'indexable_count' => 0,
            'sitemap_eligible' => 0,
            'issues' => [],
        ];

        $services = $this->resolveServices($onlyService);
        $bulkMode = $onlyService === null && $services->count() > 1;

        $run = function () use ($services, &$report, $refreshExisting): void {
            foreach ($services as $service) {
                $report['services_processed']++;
                $service->loadMissing(['pincodes', 'categories', 'locationPages.pincode', 'locationPages.page']);

                $syncResult = $this->locationProvisioner->syncAllForService($service, $refreshExisting);
                $report['pages_provisioned'] += $syncResult['created'];
                $report['pages_updated'] += $syncResult['updated'];
                $report['pages_removed'] += $syncResult['removed'];

                if (! app(ImportSideEffectsGate::class)->suppressed()) {
                    $this->linkDispatcher->dispatchForService($service->id);
                }

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
        };

        if ($bulkMode) {
            app(ImportSideEffectsGate::class)->run($run);
            app(SitemapRegenerationDispatcher::class)->dispatch();
            foreach ($services as $service) {
                $this->linkDispatcher->dispatchForService($service->id, includePeers: false);
            }
        } else {
            $run();
        }

        if ($purgeCatalogOrphans) {
            app(DownstreamArtifactPurger::class)->purgeAllCatalogOrphans();
        }

        return $report;
    }

    /**
     * @param  iterable<int, Service|int|null>  $services
     */
    public function reconcileMany(iterable $services, bool $purgeCatalogOrphans = true, bool $refreshExisting = false): array
    {
        $merged = [
            'services_processed' => 0,
            'pages_provisioned' => 0,
            'pages_updated' => 0,
            'pages_removed' => 0,
            'indexable_count' => 0,
            'sitemap_eligible' => 0,
            'issues' => [],
        ];

        $batch = [];
        foreach ($services as $service) {
            if (is_int($service)) {
                $service = Service::query()->find($service);
            }

            if ($service instanceof Service) {
                $batch[$service->id] = $service;
            }
        }

        if ($batch === []) {
            return $merged;
        }

        $merged = app(ImportSideEffectsGate::class)->run(function () use ($batch, $merged, $refreshExisting): array {
            foreach ($batch as $service) {
                $partial = $this->reconcile($service, purgeCatalogOrphans: false, refreshExisting: $refreshExisting);
                foreach (array_keys($merged) as $key) {
                    if ($key === 'issues') {
                        $merged['issues'] = array_merge($merged['issues'], $partial['issues']);

                        continue;
                    }

                    $merged[$key] += $partial[$key];
                }
            }

            return $merged;
        });

        if (count($batch) > 0) {
            app(SitemapRegenerationDispatcher::class)->dispatch();
            foreach ($batch as $service) {
                $this->linkDispatcher->dispatchForService($service->id, includePeers: false);
            }
        }

        if ($purgeCatalogOrphans) {
            app(DownstreamArtifactPurger::class)->purgeAllCatalogOrphans();
        }

        return $merged;
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
            ->orderBy('id')
            ->get();
    }
}
