<?php

namespace App\Services\Import;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePincode;
use App\Services\Governance\MappingProtectionService;
use App\Services\Governance\MasterDataProtection;

final class MappingEntityImporter extends AbstractSpreadsheetImporter
{
    public function entityKey(): string
    {
        return 'mappings';
    }

    protected function requiredColumns(): array
    {
        return ['service_code', 'pincode'];
    }

    protected function optionalColumns(): array
    {
        return [
            'priority', 'is_visible', 'is_featured', 'coverage_notes',
            'category_filter_codes', 'effective_from', 'effective_until',
        ];
    }

    protected function previewRow(array $row, int $line): array
    {
        $serviceCode = trim((string) ($row['service_code'] ?? ''));
        $pincode = trim((string) ($row['pincode'] ?? ''));

        if ($serviceCode === '' || $pincode === '') {
            return ['status' => 'invalid', 'detail' => __('Missing service or pincode.'), 'key' => null];
        }

        $service = Service::query()->where('service_code', $serviceCode)->exists();
        $pin = PinCode::query()->where('pincode', $pincode)->exists();

        if (! $service || ! $pin) {
            return [
                'status' => 'invalid',
                'detail' => ! $service ? __('Service not found.') : __('Pincode not found.'),
                'key' => "{$serviceCode}@{$pincode}",
            ];
        }

        return ['status' => 'ready', 'detail' => null, 'key' => "{$serviceCode}@{$pincode}"];
    }

    protected function importRow(array $row, int $line): array
    {
        $serviceCode = trim((string) ($row['service_code'] ?? ''));
        $pincode = trim((string) ($row['pincode'] ?? ''));

        $service = Service::query()->where('service_code', $serviceCode)->first();
        $pin = PinCode::query()->where('pincode', $pincode)->first();

        if ($service === null || $pin === null) {
            return ['action' => 'failed', 'error' => __('Service or pincode not found.')];
        }

        if (! app(MasterDataProtection::class)->allowsWrite('import')) {
            return ['action' => 'skipped', 'error' => __('Import blocked by master data protection.')];
        }

        if (! app(MappingProtectionService::class)->canAttachServicePincode($serviceCode, $pincode, 'import')) {
            return ['action' => 'skipped', 'error' => __('Mapping was removed by admin; import skipped.')];
        }

        $existing = ServicePincode::query()
            ->where('service_id', $service->id)
            ->where('pincode_id', $pin->id)
            ->first();

        $previous = $existing?->toArray();

        $pivot = [
            'priority' => filled($row['priority'] ?? null) ? (int) $row['priority'] : 0,
            'is_visible' => ImportSupport::parseBool($row['is_visible'] ?? null, true),
            'is_featured' => ImportSupport::parseBool($row['is_featured'] ?? null),
            'coverage_notes' => $row['coverage_notes'] ?? null,
            'category_filter_ids' => $this->resolveCategoryFilterIds($row['category_filter_codes'] ?? null),
            'effective_from' => filled($row['effective_from'] ?? null) ? $row['effective_from'] : null,
            'effective_until' => filled($row['effective_until'] ?? null) ? $row['effective_until'] : null,
        ];

        $service->pincodes()->syncWithoutDetaching([$pin->id => $pivot]);

        $pivotId = ServicePincode::query()
            ->where('service_id', $service->id)
            ->where('pincode_id', $pin->id)
            ->value('id');

        $this->recorder->record(
            $existing === null ? 'created' : 'updated',
            'service_pincode',
            $pivotId ? (int) $pivotId : null,
            $previous,
            $line
        );

        return ['action' => $existing === null ? 'created' : 'updated', 'error' => null];
    }

    /**
     * @return list<int>|null
     */
    private function resolveCategoryFilterIds(?string $codes): ?array
    {
        $list = ImportSupport::parseList($codes);
        if ($list === []) {
            return null;
        }

        return ServiceCategory::query()
            ->whereIn('code', array_map(fn ($c) => ServiceCategory::normalizeCode($c), $list))
            ->pluck('id')
            ->all();
    }
}
