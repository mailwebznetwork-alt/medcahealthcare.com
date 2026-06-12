<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;
use App\Services\Governance\UniversalPageRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use function Illuminate\Support\defer;

/**
 * Enforces catalog operations rules in-process (never detached shell processes).
 *
 * Category GEO → primary services → location matrix → category pages → registry.
 * Service save → location matrix reconcile.
 */
final class CatalogOperationsCascade
{
    public function __construct(
        private readonly ServiceLocationMatrixReconciler $matrixReconciler,
        private readonly CategoryMasterOrchestrator $categoryOrchestrator,
        private readonly CatalogOptimizationScorer $optimizationScorer,
        private readonly UniversalPageRegistry $pageRegistry,
    ) {}

    /**
     * @param  list<int>  $serviceIds
     */
    public function afterCategorySaved(ServiceCategory $category, array $serviceIds): void
    {
        $categoryId = (int) $category->id;
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));

        defer(function () use ($categoryId, $serviceIds): void {
            @set_time_limit(0);

            try {
                if ($serviceIds !== []) {
                    $this->matrixReconciler->reconcileMany($serviceIds, purgeCatalogOrphans: false);
                }

                $category = ServiceCategory::query()->find($categoryId);
                if ($category === null) {
                    return;
                }

                $this->categoryOrchestrator->sync($category);
                $this->optimizationScorer->scoreAndPersist(
                    $category->fresh(['seo', 'faqs', 'schema', 'pincodes'])
                );
                $this->pageRegistry->syncAll();
                $this->recordSuccess('category', $category->code);
            } catch (\Throwable $exception) {
                $this->recordFailure('category', (string) $categoryId, $exception);
            }
        });
    }

    public function afterServiceSaved(int $serviceId): void
    {
        if ($serviceId <= 0) {
            return;
        }

        defer(function () use ($serviceId): void {
            @set_time_limit(0);

            try {
                $this->matrixReconciler->reconcileMany([$serviceId], purgeCatalogOrphans: false);
                $this->pageRegistry->syncAll();
                $this->recordSuccess('service', (string) $serviceId);
            } catch (\Throwable $exception) {
                $this->recordFailure('service', (string) $serviceId, $exception);
            }
        });
    }

    /**
     * @param  list<int>  $serviceIds
     */
    public function reconcileServicesNow(array $serviceIds): void
    {
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
        if ($serviceIds === []) {
            return;
        }

        @set_time_limit(0);
        $this->matrixReconciler->reconcileMany($serviceIds, purgeCatalogOrphans: false);
        $this->pageRegistry->syncAll();
    }

    private function recordSuccess(string $scope, string $key): void
    {
        Cache::put('catalog_cascade.last_success_at', now()->toIso8601String(), now()->addDays(7));
        Cache::forget('catalog_cascade.last_failure');
        Log::info('Catalog cascade completed.', ['scope' => $scope, 'key' => $key]);
    }

    private function recordFailure(string $scope, string $key, \Throwable $exception): void
    {
        Cache::put('catalog_cascade.last_failure', [
            'scope' => $scope,
            'key' => $key,
            'message' => $exception->getMessage(),
            'at' => now()->toIso8601String(),
        ], now()->addDays(7));

        Log::error('Catalog cascade failed.', [
            'scope' => $scope,
            'key' => $key,
            'error' => $exception->getMessage(),
        ]);
    }
}
