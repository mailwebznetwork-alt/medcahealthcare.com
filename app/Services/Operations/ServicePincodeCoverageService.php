<?php

namespace App\Services\Operations;

use App\Models\AdminRemovedMapping;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePincode;
use App\Models\SubService;
use App\Services\Governance\MappingProtectionService;
use App\Services\Governance\PinCodeCreationGuard;

/**
 * Category-master pincode coverage with service manual overrides and sub-service exclusions.
 */
final class ServicePincodeCoverageService
{
    public function __construct(
        private readonly MappingProtectionService $mappingProtection,
        private readonly PinCodeCreationGuard $pinCodeGuard,
        private readonly ServiceLocationMatrixReconciler $matrixReconciler,
    ) {}

    /**
     * @param  list<int|string>  $pinIds
     */
    public function syncCategoryPincodes(ServiceCategory $category, array $pinIds, string $source = 'ui'): void
    {
        $pinIds = $this->normalizePinIds($pinIds);
        $previousIds = $category->pincodes()->pluck('pin_codes.id')->map(fn ($id) => (int) $id)->all();
        $removedIds = array_values(array_diff($previousIds, $pinIds));

        $affectedServiceIds = [];

        foreach ($removedIds as $pinId) {
            $pin = PinCode::query()->find($pinId);
            if ($pin === null) {
                continue;
            }

            if ($source === 'ui') {
                AdminRemovedMapping::recordCategoryPincodeRemoval($category->code, $pin->pincode, $source);
            }

            $affectedServiceIds = array_merge(
                $affectedServiceIds,
                $this->stripPincodeFromCategoryServices($category, $pin),
            );
        }

        foreach (array_values(array_diff($pinIds, $previousIds)) as $pinId) {
            $pin = PinCode::query()->find($pinId);
            if ($pin !== null) {
                AdminRemovedMapping::clearCategoryPincodeRemoval($category->code, $pin->pincode);
            }
        }

        $category->pincodes()->sync($pinIds);
        $affectedServiceIds = array_merge($affectedServiceIds, $this->propagateCategoryToServices($category));

        $this->reconcileAffectedServices($affectedServiceIds);
    }

    /**
     * @return list<int>
     */
    public function propagateCategoryToServices(ServiceCategory $category): array
    {
        $category->loadMissing('services');
        $affected = [];

        foreach ($category->services as $service) {
            $primary = $service->primaryCategory();
            if ($primary === null || (int) $primary->id !== (int) $category->id) {
                continue;
            }

            $this->reconcileServicePincodes($service, 'category', reconcileMatrix: false);
            $affected[] = (int) $service->id;
        }

        return $affected;
    }

    public function reconcileServicePincodes(Service $service, string $trigger = 'system', bool $reconcileMatrix = true): void
    {
        $service->loadMissing(['pincodes', 'categories']);
        $primary = $service->primaryCategory();
        $categoryPinIds = $primary !== null
            ? $this->attachableCategoryPinIds($primary)
            : [];

        $manualPinIds = $service->pincodes
            ->filter(fn ($pin) => ($pin->pivot?->pin_source ?? ServicePincode::SOURCE_MANUAL) === ServicePincode::SOURCE_MANUAL)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $targetIds = array_values(array_unique(array_merge($categoryPinIds, $manualPinIds)));
        $targetIds = $this->mappingProtection->filterAttachablePinIds($service, $targetIds, $trigger);

        $this->syncServicePivot($service, $targetIds, $categoryPinIds);

        if ($reconcileMatrix) {
            $this->deferMatrixReconcile($service);
        }
    }

    /**
     * @param  list<int|string>  $submittedPinIds
     */
    public function applyServiceGeoSelection(Service $service, array $submittedPinIds, string $source = 'ui'): void
    {
        $service->loadMissing(['pincodes', 'categories']);
        $submittedPinIds = $this->normalizePinIds($submittedPinIds);
        $primary = $service->primaryCategory();
        $categoryPinIds = $primary !== null ? $this->attachableCategoryPinIds($primary) : [];

        $previousPinIds = $service->pincodes()->pluck('pin_codes.id')->map(fn ($id) => (int) $id)->all();
        $this->mappingProtection->recordRemovalsFromSyncDiff($service, $previousPinIds, $submittedPinIds, $source);

        $manualPinIds = array_values(array_diff($submittedPinIds, $categoryPinIds));
        $categoryAttached = array_values(array_intersect($submittedPinIds, $categoryPinIds));
        $targetIds = array_values(array_unique(array_merge($categoryAttached, $manualPinIds)));
        $targetIds = $this->mappingProtection->filterAttachablePinIds($service, $targetIds, $source);

        $this->syncServicePivot($service, $targetIds, $categoryPinIds);
        $this->deferMatrixReconcile($service);
    }

    /**
     * @param  list<int|string>  $includedPinIds  Checked pincodes on the sub-service GEO tab.
     */
    public function syncSubServiceExclusions(SubService $subService, array $includedPinIds): void
    {
        $subService->loadMissing(['service.pincodes']);
        $parent = $subService->service;
        if ($parent === null) {
            return;
        }

        $includedPinIds = $this->normalizePinIds($includedPinIds);
        $parentPinIds = $parent->pincodes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $excludedIds = array_values(array_diff($parentPinIds, $includedPinIds));

        $subService->pincodeExclusions()->whereNotIn('pincode_id', $excludedIds)->delete();

        foreach ($excludedIds as $pinId) {
            $subService->pincodeExclusions()->firstOrCreate(['pincode_id' => $pinId]);
        }
    }

    /**
     * @return list<int>
     */
    private function attachableCategoryPinIds(ServiceCategory $category): array
    {
        $category->loadMissing('pincodes');

        $ids = [];
        foreach ($category->pincodes as $pin) {
            if (AdminRemovedMapping::isCategoryPincodeRemoved($category->code, $pin->pincode)) {
                continue;
            }

            $ids[] = (int) $pin->id;
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function stripPincodeFromCategoryServices(ServiceCategory $category, PinCode $pin): array
    {
        $category->loadMissing('services');
        $affected = [];

        foreach ($category->services as $service) {
            $primary = $service->primaryCategory();
            if ($primary === null || (int) $primary->id !== (int) $category->id) {
                continue;
            }

            AdminRemovedMapping::clearServicePincodeRemoval($service->service_code, $pin->pincode);
            $service->pincodes()->detach($pin->id);
            $affected[] = (int) $service->id;
        }

        return $affected;
    }

    /**
     * @param  list<int>  $serviceIds
     */
    private function reconcileAffectedServices(array $serviceIds): void
    {
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
        if ($serviceIds === []) {
            return;
        }

        $reconciler = $this->matrixReconciler;
        dispatch(function () use ($reconciler, $serviceIds): void {
            $reconciler->reconcileMany($serviceIds);
        })->afterResponse();
    }

    private function deferMatrixReconcile(Service $service): void
    {
        $serviceId = (int) $service->id;
        $reconciler = $this->matrixReconciler;

        dispatch(function () use ($reconciler, $serviceId): void {
            $fresh = Service::query()->find($serviceId);
            if ($fresh !== null) {
                $reconciler->reconcile($fresh);
            }
        })->afterResponse();
    }

    /**
     * @param  list<int>  $targetIds
     * @param  list<int>  $categoryPinIds
     */
    private function syncServicePivot(Service $service, array $targetIds, array $categoryPinIds): void
    {
        $payload = [];
        foreach ($targetIds as $pinId) {
            $payload[$pinId] = [
                'pin_source' => in_array($pinId, $categoryPinIds, true)
                    ? ServicePincode::SOURCE_CATEGORY
                    : ServicePincode::SOURCE_MANUAL,
            ];
        }

        $service->pincodes()->sync($payload);
    }

    /**
     * @param  list<int|string>  $pinIds
     * @return list<int>
     */
    private function normalizePinIds(array $pinIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            $pinIds
        ), static fn (int $id): bool => $id > 0)));

        return $this->pinCodeGuard->filterEligiblePinIdsForSync($ids);
    }
}
