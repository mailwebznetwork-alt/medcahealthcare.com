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
            $result = $this->autoMapper->map();
            if ($result['mapped']) {
                $ran[] = 'service_pincode_auto_map';
            }
        }

        return $ran;
    }

    /**
     * @return list<string>
     */
    private function commandsForEntity(string $entityKey): array
    {
        $map = [
            'categories' => ['medca:sync-category-pages', 'medca:sync-page-registry'],
            'services' => ['services:sync-master', 'medca:sync-page-registry'],
            'sub_services' => ['medca:sync-sub-service-pages', 'medca:sync-page-registry'],
            'mappings' => ['medca:reconcile-service-location-matrix', 'medca:sync-page-registry'],
            'geo' => ['medca:reconcile-service-location-matrix'],
            'pincodes' => ['medca:reconcile-service-location-matrix'],
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
}
