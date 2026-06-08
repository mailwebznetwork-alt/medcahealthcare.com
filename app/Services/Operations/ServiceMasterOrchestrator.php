<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Support\ServicePageOverrides;

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
            $this->schemaGenerator->generateAndPersist($service->fresh(['seo', 'faqs', 'pincodes']));
        }

        $this->entityGraph->persist($service->fresh());

        $service->seo()->updateOrCreate(
            ['service_id' => $service->id],
            ['canonical_url' => $this->urlBuilder->serviceUrl($service)]
        );

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
    }

    public function teardown(Service $service): void
    {
        $this->legacyRedirects->removeForService($service);
        $this->locationPageProvisioner->deleteAllForService($service);
        $this->detailPageProvisioner->deleteOwnedPage($service);
    }
}
