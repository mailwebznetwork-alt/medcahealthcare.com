<?php

namespace App\Services\Import;

use App\Services\Growth\ContentSeoAutoFillService;
use App\Services\Growth\SitemapRegenerationDispatcher;
use App\Services\Operations\CatalogGeoCoverageEnforcer;
use App\Services\Operations\ServicePincodeAutoMapper;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs post-import orchestration without modifying foundation architecture.
 */
class ImportPostSyncService
{
    private bool $autoMapDispatched = false;

    public function __construct(
        private readonly ServicePincodeAutoMapper $autoMapper,
        private readonly CatalogGeoCoverageEnforcer $geoCoverageEnforcer,
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly SitemapRegenerationDispatcher $sitemapDispatcher,
    ) {}

    /**
     * @return list<string>
     */
    public function syncForEntity(string $entityKey): array
    {
        return $this->syncForEntities([$entityKey]);
    }

    /**
     * @param  list<string>  $entityKeys
     * @return list<string>
     */
    public function syncForEntities(array $entityKeys): array
    {
        $order = config('import_registry.import_order', []);
        $commands = [];
        $autoMap = false;

        foreach ($order as $entityKey) {
            if (! in_array($entityKey, $entityKeys, true)) {
                continue;
            }

            $commands = array_merge($commands, $this->commandsForEntity($entityKey));
            if ($this->shouldAutoMap($entityKey)) {
                $autoMap = true;
            }
        }

        $ran = [];
        foreach (array_values(array_unique($commands)) as $command) {
            Artisan::call($command);
            $ran[] = $command;
        }

        if ($autoMap && ! $this->autoMapDispatched) {
            $this->autoMapDispatched = true;
            $result = $this->autoMapper->map(provisionPages: false);
            if ($result['mapped']) {
                $ran[] = 'service_pincode_auto_map';
            }
        }

        if ($this->shouldRestoreGeoCatalog($entityKeys)) {
            $this->geoCoverageEnforcer->enforceAfterGeoRestore();
            $ran[] = 'catalog_geo_restore';
        }

        return array_merge($ran, $this->runGrowthSeoRefresh($entityKeys));
    }

    /**
     * @return list<string>
     */
    private function commandsForEntity(string $entityKey): array
    {
        $map = [
            'categories' => ['medca:propagate-all-category-pincodes', 'medca:sync-category-pages', 'medca:sync-page-registry'],
            'services' => ['services:sync-master', 'medca:sync-page-registry'],
            'sub_services' => ['medca:sync-sub-service-pages', 'medca:sync-page-registry'],
            'mappings' => ['medca:reconcile-service-location-matrix', 'medca:sync-page-registry'],
            'geo' => ['medca:reconcile-service-location-matrix', 'medca:sync-page-registry'],
            'pincodes' => ['medca:reconcile-service-location-matrix', 'medca:sync-page-registry'],
        ];

        return $map[$entityKey] ?? [];
    }

    private function shouldAutoMap(string $entityKey): bool
    {
        if (! config('import_registry.workflow.auto_map_service_pincodes', true)) {
            return false;
        }

        return in_array($entityKey, ['pincodes', 'geo', 'services'], true);
    }

    /**
     * @param  list<string>  $entityKeys
     */
    private function shouldRestoreGeoCatalog(array $entityKeys): bool
    {
        return array_intersect($entityKeys, ['pincodes', 'geo', 'categories']) !== [];
    }

    /**
     * @param  list<string>  $entityKeys
     * @return list<string>
     */
    private function runGrowthSeoRefresh(array $entityKeys): array
    {
        $catalogKeys = ['categories', 'services', 'sub_services', 'pincodes', 'mappings', 'geo'];
        if (array_intersect($entityKeys, $catalogKeys) === []) {
            return [];
        }

        $ran = [];

        if (array_intersect($entityKeys, ['services', 'sub_services', 'categories']) !== []) {
            Artisan::call('medca:fill-quick-answers');
            $ran[] = 'medca:fill-quick-answers';
        }

        $this->contentSeoAutoFill->refreshAggregateSignals();
        $ran[] = 'content_seo_aggregate_refresh';

        $this->sitemapDispatcher->dispatch();
        $ran[] = 'sitemap_regeneration_dispatched';

        if (array_intersect($entityKeys, ['services', 'sub_services', 'categories']) !== []) {
            \App\Jobs\NotifyContentQualityAlertsJob::dispatch('post_import');
            $ran[] = 'content_quality_alert_dispatched';
        }

        return $ran;
    }
}
