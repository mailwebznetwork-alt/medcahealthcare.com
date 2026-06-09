<?php

namespace App\Services\Import;

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
    ) {}

    /**
     * @return list<string>
     */
    public function syncForEntity(string $entityKey): array
    {
        $ran = [];

        $map = [
            'categories' => ['medca:sync-category-pages', 'medca:sync-page-registry'],
            'services' => ['services:sync-master', 'medca:sync-page-registry'],
            'sub_services' => ['medca:sync-sub-service-pages', 'medca:sync-page-registry'],
            'mappings' => ['medca:reconcile-service-location-matrix', 'medca:sync-page-registry'],
            'geo' => ['medca:reconcile-service-location-matrix'],
            'pincodes' => ['medca:reconcile-service-location-matrix'],
        ];

        foreach ($map[$entityKey] ?? [] as $command) {
            Artisan::call($command);
            $ran[] = $command;
        }

        if ($this->shouldAutoMap($entityKey) && ! $this->autoMapDispatched) {
            $this->autoMapDispatched = true;
            $result = $this->autoMapper->map();
            if ($result['mapped']) {
                $ran[] = 'service_pincode_auto_map';
            }
        }

        return $ran;
    }

    private function shouldAutoMap(string $entityKey): bool
    {
        if (! config('import_registry.workflow.auto_map_service_pincodes', true)) {
            return false;
        }

        return in_array($entityKey, ['pincodes', 'geo', 'services'], true);
    }
}
