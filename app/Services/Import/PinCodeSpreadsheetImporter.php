<?php

namespace App\Services\Import;

use App\Models\PinCode;
use App\Services\Governance\MasterDataProtection;
use App\Services\Governance\PinCodeCreationGuard;
use App\Services\Governance\PinCodeMasterDataAudit;

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

    public function upsertExisting(): bool
    {
        return $this->upsertExisting;
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}  $parsed
     * @return array{
     *     total_rows: int,
     *     unique_pincodes: int,
     *     duplicate_rows: int,
     *     invalid_rows: int,
     *     would_create: int,
     *     would_update: int,
     *     would_restore: int,
     *     would_skip: int
     * }
     */
    public function summarizeParsed(array $parsed): array
    {
        $deduped = $this->dedupeParsedRowsByPincode($parsed);
        $stats = [
            'total_rows' => 0,
            'unique_pincodes' => 0,
            'duplicate_rows' => 0,
            'invalid_rows' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'would_restore' => 0,
            'would_skip' => 0,
        ];

        $line = 1;
        $seenPins = [];
        foreach ($parsed['rows'] as $rawRow) {
            $line++;
            $mapped = $this->reader->mapRow($parsed['headers'], $rawRow);
            if ($this->rowIsBlank($mapped)) {
                continue;
            }

            $stats['total_rows']++;
            $pin = $this->normalizePincode($mapped['pincode'] ?? null);
            if ($pin === null) {
                $stats['invalid_rows']++;

                continue;
            }

            if (isset($seenPins[$pin])) {
                $stats['duplicate_rows']++;
            }
            $seenPins[$pin] = true;
        }

        $line = 1;
        foreach ($deduped['rows'] as $rawRow) {
            $line++;
            $mapped = $this->reader->mapRow($deduped['headers'], $rawRow);
            if ($this->rowIsBlank($mapped)) {
                continue;
            }

            $preview = $this->previewRow($mapped, $line);
            $stats['unique_pincodes']++;

            match ($preview['status']) {
                'ready' => $stats['would_create']++,
                'update' => $this->isRestorePreview($mapped) ? $stats['would_restore']++ : $stats['would_update']++,
                'duplicate' => $stats['would_skip']++,
                default => null,
            };
        }

        return $stats;
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}  $parsed
     * @return array{valid: bool, errors: list<string>, rows: list<array<string, mixed>>, total_data_rows: int}
     */
    public function previewParsed(array $parsed, int $limit = 25): array
    {
        return parent::previewParsed($this->dedupeParsedRowsByPincode($parsed), $limit);
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}  $parsed
     * @return array{created: int, updated: int, skipped: int, failed: int, errors: list<string>}
     */
    public function importParsed(array $parsed): array
    {
        return parent::importParsed($this->dedupeParsedRowsByPincode($parsed));
    }

    /**
     * Collapse repeated pincode rows — last row wins (common in exported workbooks).
     *
     * @param  array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}  $parsed
     * @return array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}
     */
    private function dedupeParsedRowsByPincode(array $parsed): array
    {
        $headers = $parsed['headers'];
        $invalidRows = [];
        $lastByPin = [];

        foreach ($parsed['rows'] as $rawRow) {
            $mapped = $this->reader->mapRow($headers, $rawRow);
            if ($this->rowIsBlank($mapped)) {
                continue;
            }

            $pin = $this->normalizePincode($mapped['pincode'] ?? null);
            if ($pin === null) {
                $invalidRows[] = $rawRow;

                continue;
            }

            $lastByPin[$pin] = $rawRow;
        }

        $parsed['rows'] = array_merge($invalidRows, array_values($lastByPin));
        $parsed['total_data_rows'] = count($parsed['rows']);

        return $parsed;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function isRestorePreview(array $row): bool
    {
        $pin = $this->normalizePincode($row['pincode'] ?? null);
        if ($pin === null) {
            return false;
        }

        return PinCode::withTrashed()->where('pincode', $pin)->whereNotNull('deleted_at')->exists()
            && ! PinCode::query()->where('pincode', $pin)->exists();
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
            'state', 'locality', 'bangalore_zone_code', 'is_serviceable', 'is_active', 'delivery_charge',
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

        $active = PinCode::query()->where('pincode', $pin)->exists();
        $trashed = PinCode::withTrashed()->where('pincode', $pin)->whereNotNull('deleted_at')->exists();

        if ($trashed && ! $active) {
            return [
                'status' => 'update',
                'detail' => __('Will restore previously deleted pincode.'),
                'key' => $pin,
            ];
        }

        return [
            'status' => $active ? ($this->upsertExisting ? 'update' : 'duplicate') : 'ready',
            'detail' => $active
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

        if (! app(MasterDataProtection::class)->allowsWrite('import')) {
            app(PinCodeMasterDataAudit::class)->recreationBlocked($pin, 'import', 'Master data protection is enabled.');

            return ['action' => 'skipped', 'error' => __('Import blocked by master data protection.')];
        }

        $guard = app(PinCodeCreationGuard::class);
        $restored = $guard->resolveForExplicitRecreate($pin, 'import');
        $existing = PinCode::query()->where('pincode', $pin)->first() ?? $restored;

        if ($existing === null && ! $guard->canCreatePincode($pin, 'import')) {
            return ['action' => 'skipped', 'error' => __('Pincode cannot be imported.')];
        }

        if ($existing !== null && ! $this->upsertExisting && $restored === null) {
            return ['action' => 'skipped', 'error' => null];
        }

        $attrs = [
            'pincode' => $pin,
            'area_name' => $area,
            'city' => $city,
            'state' => $row['state'] ?? null,
            'locality' => $row['locality'] ?? null,
            'bangalore_zone_id' => $this->resolveBangaloreZoneId($row['bangalore_zone_code'] ?? null),
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
            app(PinCodeMasterDataAudit::class)->created($pinCode, 'import');

            return ['action' => 'created', 'error' => null];
        }

        $previous = $existing->toArray();
        $existing->update($attrs);
        $this->recorder->record('updated', 'pin_code', $existing->id, $previous, $line);
        app(PinCodeMasterDataAudit::class)->updated($existing, 'import');

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

    private function resolveBangaloreZoneId(?string $code): ?int
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $zone = \App\Models\BangaloreZone::query()->where('code', $code)->first();

        return $zone?->id;
    }
}
