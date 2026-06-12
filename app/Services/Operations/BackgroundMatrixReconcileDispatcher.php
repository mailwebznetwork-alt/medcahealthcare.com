<?php

namespace App\Services\Operations;

/**
 * @deprecated Use CatalogOperationsCascade — kept for call-site compatibility.
 */
final class BackgroundMatrixReconcileDispatcher
{
    public function __construct(
        private readonly CatalogOperationsCascade $cascade,
    ) {}

    /**
     * @param  list<int>  $serviceIds
     */
    public function dispatchMany(array $serviceIds): void
    {
        foreach (array_values(array_unique(array_filter(array_map('intval', $serviceIds)))) as $serviceId) {
            $this->cascade->afterServiceSaved($serviceId);
        }
    }

    public function dispatchOne(int $serviceId): void
    {
        $this->cascade->afterServiceSaved($serviceId);
    }
}
