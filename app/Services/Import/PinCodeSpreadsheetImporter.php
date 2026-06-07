<?php

namespace App\Services\Import;

use App\Models\PinCode;

/**
 * Pincode directory import — CSV, XLS, XLSX.
 */
class PinCodeSpreadsheetImporter extends AbstractSpreadsheetImporter
{
    private bool $upsertExisting = false;

    public function withUpsert(bool $upsert = true): self
    {
        $this->upsertExisting = $upsert;

        return $this;
    }

    public function entityKey(): string
    {
        return 'pincodes';
    }

    protected function requiredColumns(): array
    {
        return ['pincode', 'area_name', 'city'];
    }

    protected function optionalColumns(): array
    {
        return [
            'state', 'locality', 'is_serviceable', 'is_active', 'delivery_charge',
            'meta_title', 'meta_description', 'seo_keywords', 'priority',
            'service_radius_km', 'coverage_type',
        ];
    }

    protected function previewRow(array $row, int $line): array
    {
        $pin = $this->normalizePincode($row['pincode'] ?? null);
        if ($pin === null) {
            return ['status' => 'invalid', 'detail' => __('Invalid pincode.'), 'key' => null];
        }

        $duplicate = PinCode::query()->where('pincode', $pin)->exists();

        return [
            'status' => $duplicate ? ($this->upsertExisting ? 'update' : 'duplicate') : 'ready',
            'detail' => $duplicate
                ? ($this->upsertExisting ? __('Will update existing pincode.') : __('Already in directory (will be skipped).'))
                : null,
            'key' => $pin,
        ];
    }

    protected function importRow(array $row, int $line): array
    {
        $pin = $this->normalizePincode($row['pincode'] ?? null);
        $area = trim((string) ($row['area_name'] ?? ''));
        $city = trim((string) ($row['city'] ?? ''));

        if ($pin === null || $area === '' || $city === '') {
            return ['action' => 'failed', 'error' => __('Invalid pincode or missing required fields.')];
        }

        $existing = PinCode::query()->where('pincode', $pin)->first();
        if ($existing !== null && ! $this->upsertExisting) {
            return ['action' => 'skipped', 'error' => null];
        }

        $attrs = [
            'pincode' => $pin,
            'area_name' => $area,
            'city' => $city,
            'state' => $row['state'] ?? null,
            'locality' => $row['locality'] ?? null,
            'is_serviceable' => ImportSupport::parseBool($row['is_serviceable'] ?? null, true),
            'is_active' => ImportSupport::parseBool($row['is_active'] ?? null, true),
            'delivery_charge' => filled($row['delivery_charge'] ?? null) && is_numeric($row['delivery_charge']) ? $row['delivery_charge'] : null,
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'seo_keywords' => $row['seo_keywords'] ?? null,
            'priority' => filled($row['priority'] ?? null) ? (int) $row['priority'] : 0,
            'geo_page_ready' => false,
        ];

        $custom = ImportSupport::extractCustomFields($row, ['service_radius_km', 'coverage_type']);
        if ($custom !== []) {
            $attrs['custom_fields'] = array_merge($existing?->custom_fields ?? [], $custom);
        }

        if ($existing === null) {
            $pinCode = PinCode::query()->create($attrs);
            $this->recorder->record('created', 'pin_code', $pinCode->id, null, $line);

            return ['action' => 'created', 'error' => null];
        }

        $previous = $existing->toArray();
        $existing->update($attrs);
        $this->recorder->record('updated', 'pin_code', $existing->id, $previous, $line);

        return ['action' => 'updated', 'error' => null];
    }

    private function normalizePincode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $pin = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($pin) < 6 || strlen($pin) > 12) {
            return null;
        }

        return $pin;
    }
}
