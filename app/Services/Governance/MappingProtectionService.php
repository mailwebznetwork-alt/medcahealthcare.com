<?php

namespace App\Services\Governance;

use App\Models\AdminRemovedMapping;
use App\Models\PinCode;
use App\Models\Service;

final class MappingProtectionService
{
    public function __construct(
        private readonly MasterDataAudit $audit,
        private readonly MasterDataProtection $protection,
    ) {}

    public function canAttachServicePincode(string $serviceCode, string $pincode, string $source = 'system'): bool
    {
        if (! AdminRemovedMapping::isServicePincodeRemoved($serviceCode, $pincode)) {
            return true;
        }

        if (in_array($source, ['ui', 'import'], true)) {
            return true;
        }

        $this->audit->mappingRemovalBlocked($serviceCode, $pincode, $source, 'Mapping was removed by admin.');

        return false;
    }

    public function recordServicePincodeRemoval(
        string $serviceCode,
        string $pincode,
        string $source = 'ui',
        ?string $reason = null,
    ): void {
        AdminRemovedMapping::recordServicePincodeRemoval($serviceCode, $pincode, $source, $reason);
        $this->audit->mappingRemoved($serviceCode, $pincode, $source, $reason);
    }

    /**
     * @param  list<int>  $previousPinIds
     * @param  list<int>  $newPinIds
     */
    public function recordRemovalsFromSyncDiff(Service $service, array $previousPinIds, array $newPinIds, string $source = 'ui'): void
    {
        if ($source !== 'ui') {
            return;
        }

        $removedIds = array_values(array_diff(
            array_map('intval', $previousPinIds),
            array_map('intval', $newPinIds),
        ));

        if ($removedIds === []) {
            return;
        }

        $pins = PinCode::query()->whereIn('id', $removedIds)->get(['id', 'pincode']);
        foreach ($pins as $pin) {
            $this->recordServicePincodeRemoval($service->service_code, $pin->pincode, $source);
        }
    }

    /**
     * @param  list<int>  $pinIds
     * @return list<int>
     */
    public function filterAttachablePinIds(Service $service, array $pinIds, string $source = 'system'): array
    {
        if ($pinIds === []) {
            return [];
        }

        $pins = PinCode::query()->whereIn('id', array_map('intval', $pinIds))->get(['id', 'pincode']);
        $allowed = [];

        foreach ($pins as $pin) {
            if ($this->canAttachServicePincode($service->service_code, $pin->pincode, $source)) {
                $allowed[] = (int) $pin->id;
            }
        }

        return $allowed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $syncPayload
     * @return array<int, array<string, mixed>>
     */
    public function filterSyncPayload(Service $service, array $syncPayload, string $source = 'system'): array
    {
        if ($syncPayload === []) {
            return [];
        }

        $pinIds = array_map('intval', array_keys($syncPayload));
        $allowedIds = $this->filterAttachablePinIds($service, $pinIds, $source);
        $filtered = [];

        foreach ($allowedIds as $id) {
            if (isset($syncPayload[$id])) {
                $filtered[$id] = $syncPayload[$id];
            }
        }

        return $filtered;
    }

    /**
     * @param  list<int>  $pinIds
     * @return list<int>
     */
    public function filterPinIdsForBulkAttach(string $serviceCode, array $pinIds, string $source = 'system'): array
    {
        if ($pinIds === []) {
            return [];
        }

        $pins = PinCode::query()->whereIn('id', array_map('intval', $pinIds))->get(['id', 'pincode']);
        $allowed = [];

        foreach ($pins as $pin) {
            if ($this->canAttachServicePincode($serviceCode, $pin->pincode, $source)) {
                $allowed[] = (int) $pin->id;
            }
        }

        return $allowed;
    }
}
