<?php

namespace App\Services\Import;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\PinCode;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCompareService
{
    /**
     * Compare a staged spreadsheet against live database keys.
     *
     * @return array{
     *     entity: string,
     *     would_create: int,
     *     would_update: int,
     *     unchanged: int,
     *     field_diff_samples: list<array{key: string, fields: list<string>}>
     * }
     */
    public function compareEntityFile(string $entityKey, string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0] ?? []);
        $headerIndex = array_flip($headers);

        $wouldCreate = 0;
        $wouldUpdate = 0;
        $unchanged = 0;
        $diffSamples = [];

        foreach (array_slice($rows, 1) as $row) {
            $mapped = [];
            foreach ($headerIndex as $col => $idx) {
                $mapped[$col] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            }

            $comparison = $this->compareRow($entityKey, $mapped);
            match ($comparison['status']) {
                'create' => $wouldCreate++,
                'update' => $wouldUpdate++,
                default => $unchanged++,
            };

            if ($comparison['status'] === 'update' && ! empty($comparison['changed_fields']) && count($diffSamples) < 15) {
                $diffSamples[] = [
                    'key' => $comparison['key'],
                    'fields' => $comparison['changed_fields'],
                ];
            }
        }

        return [
            'entity' => $entityKey,
            'would_create' => $wouldCreate,
            'would_update' => $wouldUpdate,
            'unchanged' => $unchanged,
            'field_diff_samples' => $diffSamples,
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: string, key: string, changed_fields: list<string>}
     */
    private function compareRow(string $entityKey, array $row): array
    {
        return match ($entityKey) {
            'services' => $this->compareService($row),
            'categories' => $this->compareCategory($row),
            'sub_services' => $this->compareSubService($row),
            'pincodes' => $this->comparePincode($row),
            default => ['status' => 'skip', 'key' => '', 'changed_fields' => []],
        };
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: string, key: string, changed_fields: list<string>}
     */
    private function compareService(array $row): array
    {
        $code = $row['service_code'] ?? '';
        if ($code === '') {
            return ['status' => 'skip', 'key' => '', 'changed_fields' => []];
        }

        $existing = Service::query()->where('service_code', $code)->first();
        if ($existing === null) {
            return ['status' => 'create', 'key' => $code, 'changed_fields' => []];
        }

        $changed = [];
        foreach (['title', 'short_summary', 'description', 'quick_answer', 'ai_summary'] as $field) {
            if (isset($row[$field]) && $row[$field] !== '' && (string) $existing->{$field} !== $row[$field]) {
                $changed[] = $field;
            }
        }

        return [
            'status' => $changed === [] ? 'unchanged' : 'update',
            'key' => $code,
            'changed_fields' => $changed,
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: string, key: string, changed_fields: list<string>}
     */
    private function compareCategory(array $row): array
    {
        $code = $row['code'] ?? '';
        if ($code === '') {
            return ['status' => 'skip', 'key' => '', 'changed_fields' => []];
        }

        $existing = ServiceCategory::query()->where('code', $code)->first();
        if ($existing === null) {
            return ['status' => 'create', 'key' => $code, 'changed_fields' => []];
        }

        $changed = [];
        if (isset($row['name']) && $row['name'] !== '' && $existing->name !== $row['name']) {
            $changed[] = 'name';
        }

        return [
            'status' => $changed === [] ? 'unchanged' : 'update',
            'key' => $code,
            'changed_fields' => $changed,
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: string, key: string, changed_fields: list<string>}
     */
    private function compareSubService(array $row): array
    {
        $code = $row['sub_service_code'] ?? '';
        if ($code === '') {
            return ['status' => 'skip', 'key' => '', 'changed_fields' => []];
        }

        $existing = SubService::query()->where('sub_service_code', $code)->first();

        return $existing === null
            ? ['status' => 'create', 'key' => $code, 'changed_fields' => []]
            : ['status' => 'unchanged', 'key' => $code, 'changed_fields' => []];
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: string, key: string, changed_fields: list<string>}
     */
    private function comparePincode(array $row): array
    {
        $pin = preg_replace('/\D/', '', $row['pincode'] ?? '') ?? '';
        if (strlen($pin) < 6) {
            return ['status' => 'skip', 'key' => '', 'changed_fields' => []];
        }

        $existing = PinCode::query()->where('pincode', $pin)->first();
        if ($existing === null) {
            return ['status' => 'create', 'key' => $pin, 'changed_fields' => []];
        }

        $changed = [];
        if (isset($row['area_name']) && $row['area_name'] !== '' && $existing->area_name !== $row['area_name']) {
            $changed[] = 'area_name';
        }

        return [
            'status' => $changed === [] ? 'unchanged' : 'update',
            'key' => $pin,
            'changed_fields' => $changed,
        ];
    }
}
