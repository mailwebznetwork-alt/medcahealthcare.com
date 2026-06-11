<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\Process;

/**
 * Spawns a detached artisan process so location-page reconciliation does not block HTTP requests.
 */
final class BackgroundMatrixReconcileDispatcher
{
    /**
     * @param  list<int>  $serviceIds
     */
    public function dispatchMany(array $serviceIds): void
    {
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
        if ($serviceIds === []) {
            return;
        }

        $artisan = base_path('artisan');
        $php = PHP_BINARY;

        Process::path(base_path())
            ->timeout(3600)
            ->start([
                $php,
                $artisan,
                'medca:reconcile-service-location-matrix',
                '--service-ids='.implode(',', $serviceIds),
            ]);
    }

    public function dispatchOne(int $serviceId): void
    {
        $this->dispatchMany([$serviceId]);
    }
}
