<?php

namespace App\Services\Import;

use App\Models\PinCode;
use App\Models\PinCodeHospital;
use App\Models\PinCodeLandmark;
use App\Models\PinCodeLocationFaq;
use App\Models\PinCodeNearbyArea;
use App\Services\Governance\MasterDataProtection;
use App\Services\Governance\PinCodeCreationGuard;
use App\Services\Governance\PinCodeMasterDataAudit;

final class GeoEnrichmentEntityImporter extends AbstractSpreadsheetImporter
{
    public function entityKey(): string
    {
        return 'geo';
    }

    protected function requiredColumns(): array
    {
        return ['pincode'];
    }

    protected function optionalColumns(): array
    {
        return [
            'area_name', 'city', 'state', 'is_serviceable', 'priority',
            'coverage_text', 'emergency_coverage_text',
            'landmark_names', 'hospital_names', 'nearby_areas', 'faq_pairs',
            'geo_entity_signals', 'local_intent_keywords',
        ];
    }

    protected function previewRow(array $row, int $line): array
    {
        $pincode = trim((string) ($row['pincode'] ?? ''));
        if ($pincode === '') {
            return ['status' => 'invalid', 'detail' => __('Missing pincode.'), 'key' => null];
        }

        $exists = PinCode::query()->where('pincode', $pincode)->exists();

        return [
            'status' => $exists ? 'update' : 'ready',
            'detail' => $exists ? __('Will enrich existing pincode.') : __('Will create pincode.'),
            'key' => $pincode,
        ];
    }

    protected function importRow(array $row, int $line): array
    {
        $pincode = trim((string) ($row['pincode'] ?? ''));
        if ($pincode === '') {
            return ['action' => 'failed', 'error' => __('Missing pincode.')];
        }

        if (! app(MasterDataProtection::class)->allowsWrite('import')) {
            app(PinCodeMasterDataAudit::class)->recreationBlocked($pincode, 'import', 'Master data protection is enabled.');

            return ['action' => 'skipped', 'error' => __('Import blocked by master data protection.')];
        }

        $guard = app(PinCodeCreationGuard::class);
        $normalized = $guard->normalizePincode($pincode) ?? $pincode;
        $restored = $guard->resolveForExplicitRecreate($normalized, 'import');
        $existing = PinCode::query()->where('pincode', $normalized)->first() ?? $restored;

        if ($existing === null && ! $guard->canCreatePincode($normalized, 'import')) {
            return ['action' => 'skipped', 'error' => __('Pincode cannot be imported.')];
        }
        $previous = $existing?->toArray();

        $attrs = array_filter([
            'pincode' => $pincode,
            'area_name' => $row['area_name'] ?? ($existing?->area_name ?? 'Area '.$pincode),
            'city' => $row['city'] ?? ($existing?->city ?? null),
            'state' => $row['state'] ?? ($existing?->state ?? null),
            'is_serviceable' => ImportSupport::parseBool($row['is_serviceable'] ?? null, true),
            'is_active' => true,
            'priority' => filled($row['priority'] ?? null) ? (int) $row['priority'] : ($existing?->priority ?? 0),
            'coverage_text' => $row['coverage_text'] ?? null,
            'emergency_coverage_text' => $row['emergency_coverage_text'] ?? null,
            'geo_page_ready' => true,
        ], fn ($v) => $v !== null);

        if ($existing === null) {
            $pin = PinCode::query()->create($attrs);
            $this->recorder->record('created', 'pin_code', $pin->id, null, $line);
            app(PinCodeMasterDataAudit::class)->created($pin, 'import');
            $action = 'created';
        } else {
            $existing->update($attrs);
            $pin = $existing->fresh();
            $this->recorder->record('updated', 'pin_code', $pin->id, $previous, $line);
            app(PinCodeMasterDataAudit::class)->updated($pin, 'import');
            $action = 'updated';
        }

        $custom = ImportSupport::extractCustomFields($row, ['geo_entity_signals', 'local_intent_keywords']);
        if ($custom !== []) {
            $pin->forceFill(['custom_fields' => array_merge($pin->custom_fields ?? [], $custom)])->saveQuietly();
        }

        $this->syncLandmarks($pin, $row['landmark_names'] ?? null);
        $this->syncHospitals($pin, $row['hospital_names'] ?? null);
        $this->syncNearby($pin, $row['nearby_areas'] ?? null);
        $this->syncFaqs($pin, $row['faq_pairs'] ?? null);

        return ['action' => $action, 'error' => null];
    }

    private function syncLandmarks(PinCode $pin, ?string $names): void
    {
        foreach (ImportSupport::parseList($names, '|') as $i => $name) {
            PinCodeLandmark::query()->firstOrCreate(
                ['pincode_id' => $pin->id, 'name' => $name],
                ['sort_order' => $i]
            );
        }
    }

    private function syncHospitals(PinCode $pin, ?string $names): void
    {
        foreach (ImportSupport::parseList($names, '|') as $i => $name) {
            PinCodeHospital::query()->firstOrCreate(
                ['pincode_id' => $pin->id, 'name' => $name],
                ['sort_order' => $i]
            );
        }
    }

    private function syncNearby(PinCode $pin, ?string $areas): void
    {
        foreach (ImportSupport::parseList($areas, '|') as $i => $area) {
            PinCodeNearbyArea::query()->firstOrCreate(
                ['pincode_id' => $pin->id, 'area_name' => $area],
                ['sort_order' => $i]
            );
        }
    }

    private function syncFaqs(PinCode $pin, ?string $pairs): void
    {
        foreach (ImportSupport::parseFaqPairs($pairs) as $i => $pair) {
            PinCodeLocationFaq::query()->firstOrCreate(
                ['pincode_id' => $pin->id, 'question' => $pair['question']],
                ['answer' => $pair['answer'], 'sort_order' => $i]
            );
        }
    }
}
