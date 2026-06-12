<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Support\ServicePageOverrides;
use Illuminate\Support\Collection;

/**
 * Central pipeline: Service → SEO/AEO/GEO/Schema → auto pages → URLs → links → sitemap data.
 */
class ServiceMasterOrchestrator
{
    public function __construct(
        private readonly ServiceSchemaGenerator $schemaGenerator,
        private readonly ServiceOptimizationScorer $optimizationScorer,
        private readonly ServiceDetailPageProvisioner $detailPageProvisioner,
        private readonly ServiceLocationPageProvisioner $locationPageProvisioner,
        private readonly ServiceMasterPageSync $masterPageSync,
        private readonly PageCategoryResolver $categoryResolver,
        private readonly ServiceEntityGraphBuilder $entityGraph,
        private readonly ServiceLegacyRedirectSync $legacyRedirects,
        private readonly ServicePublicUrlBuilder $urlBuilder,
    ) {}

    public function sync(Service $service, ?string $previousServiceCode = null): void
    {
        if (! config('services_master.auto_sync_on_save', true)) {
            return;
        }

        $service->loadMissing(['seo', 'faqs', 'schema', 'pincodes']);

        $service->loadMissing('schema');
        $manualSchema = $service->schema !== null
            && filled($service->schema->schema_type)
            && $service->schema->schema_type !== 'ServiceGraph';

        if (! $manualSchema) {
            $freshForSchema = $service->fresh(['seo', 'faqs', 'pincodes']);
            if ($freshForSchema !== null) {
                $this->schemaGenerator->generateAndPersist($freshForSchema);
            }
        }

        $freshForGraph = $service->fresh();
        if ($freshForGraph === null) {
            return;
        }

        $this->entityGraph->persist($freshForGraph);

        $service->seo()->updateOrCreate(
            ['service_id' => $service->id],
            ['canonical_url' => $this->urlBuilder->serviceUrl($service)]
        );

        if (! $service->isListedPublicly()) {
            $this->purgeGeneratedPagesForService($service);

            return;
        }

        if (ServiceGeneratedPageEligibility::serviceHasGeoCoverage($service)) {
            $detailPage = $this->detailPageProvisioner->syncFromService($service, $previousServiceCode);
            $detailAttributes = ServicePageOverrides::filterAutomatedAttributes($detailPage, [
                'page_category' => \App\Enums\PageCategory::Service,
                'canonical_url' => $this->urlBuilder->serviceUrl($service),
            ]);
            if ($detailAttributes !== []) {
                $detailPage->forceFill($detailAttributes)->saveQuietly();
            }

            $this->masterPageSync->pushToPage($service->fresh(), $detailPage, forceEmptyOnly: false);
            $this->categoryResolver->applyToPage($detailPage);

            $this->locationPageProvisioner->syncAllForService($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

            app(InternalLinkRefreshDispatcher::class)->dispatchForService($service->id);
            $this->optimizationScorer->scoreAndPersist($service->fresh());

            $this->legacyRedirects->syncForService($service->fresh(['detailPage', 'locationPages']));

            $service->fresh(['detailPage']);
            if ($service->detailPage !== null) {
                app(\App\Services\Governance\UniversalPageRegistry::class)->upsertServiceEntry($service);
            }
        } else {
            $this->purgeGeoDependentPagesForService($service);
        }

        $this->syncSubServicePages($service);
    }

    public function teardown(Service $service): void
    {
        $this->bulkTeardown([$service->id], collect([$service]));
    }

    /**
     * @param  list<int>  $serviceIds
     * @param  Collection<int, Service>  $services
     */
    public function bulkTeardown(array $serviceIds, Collection $services): void
    {
        $this->legacyRedirects->bulkRemoveForServiceIds($serviceIds);
        $this->locationPageProvisioner->bulkDeleteLocationArtifactsForServiceIds($serviceIds);
        $this->detailPageProvisioner->bulkDeleteOwnedPagesForServices($services);
    }

    private function purgeGeneratedPagesForService(Service $service): void
    {
        $this->purgeGeoDependentPagesForService($service);
        $this->legacyRedirects->bulkRemoveForServiceIds([$service->id]);

        $service->loadMissing('subServices');
        foreach ($service->subServices as $sub) {
            app(SubServicePageProvisioner::class)->deleteOwnedPage($sub);
        }
    }

    private function purgeGeoDependentPagesForService(Service $service): void
    {
        $this->locationPageProvisioner->bulkDeleteLocationArtifactsForServiceIds([$service->id]);
        $this->detailPageProvisioner->deleteOwnedPage($service);

        if ($service->detail_page_id !== null) {
            $service->forceFill(['detail_page_id' => null])->saveQuietly();
        }
    }

    private function syncSubServicePages(Service $service): void
    {
        if (! config('phase2_discovery.auto_sync_sub_service_pages', true)) {
            return;
        }

        $service->loadMissing('subServices');
        $orchestrator = app(SubServiceMasterOrchestrator::class);

        foreach ($service->subServices as $sub) {
            $orchestrator->sync($sub);
        }
    }
}
