<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Artisan;

/**
 * Runs post-import orchestration without modifying foundation architecture.
 */
class ImportPostSyncService
{
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

        return $ran;
    }
}
