<?php

namespace App\Services\Import;

use App\Services\Import\Contracts\EntityImporter;
use Illuminate\Support\Str;

/**
 * Dispatches master workbook uploads (services.xlsx, pincodes.xlsx) to existing entity importers.
 */
final class WorkbookImportOrchestrator
{
    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly ImportRegistry $registry,
        private readonly ImportPipeline $pipeline,
        private readonly ServiceImportDefaults $serviceDefaults,
        private readonly WorkbookImportContext $workbookContext,
    ) {}

    public function detectWorkbookKey(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $basename = strtolower(pathinfo($filename, PATHINFO_FILENAME));
        foreach (config('import_registry.workbooks', []) as $key => $meta) {
            foreach ($meta['filename_hints'] ?? [] as $hint) {
                if ($basename === strtolower(pathinfo($hint, PATHINFO_FILENAME))) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * @return array{valid: bool, workbook: string|null, sheets: list<array<string, mixed>>, errors: list<string>, total_data_rows: int}
     */
    public function preview(string $workbookKey, mixed $source, int $limit = 25): array
    {
        $plan = $this->workbookPlan($workbookKey);
        if ($plan === null) {
            return [
                'valid' => false,
                'workbook' => $workbookKey,
                'sheets' => [],
                'errors' => [__('Unknown workbook key: :key', ['key' => $workbookKey])],
                'total_data_rows' => 0,
            ];
        }

        $sheetNames = $this->reader->sheetNames($source);
        if ($sheetNames === []) {
            return [
                'valid' => false,
                'workbook' => $workbookKey,
                'sheets' => [],
                'errors' => [__('Workbook has no readable sheets.')],
                'total_data_rows' => 0,
            ];
        }

        $this->serviceDefaults->clear();
        $this->workbookContext->clear();
        $sheets = [];
        $errors = [];
        $totalRows = 0;
        $valid = true;

        foreach ($plan['sheet_order'] as $sheetKey) {
            $sheetMeta = $plan['sheets'][$sheetKey] ?? null;
            if ($sheetMeta === null) {
                continue;
            }

            if ($this->skipWorkbookSheet($workbookKey, $sheetKey, $sheetMeta)) {
                $sheets[] = $this->systemManagedSheetPreview($sheetKey);

                continue;
            }

            $resolvedName = $this->resolveSheetName($sheetNames, $sheetKey, $sheetMeta['aliases'] ?? []);
            if ($resolvedName === null) {
                if ($sheetMeta['optional'] ?? false) {
                    continue;
                }
                $valid = false;
                $errors[] = __('Missing required sheet: :sheet', ['sheet' => $sheetKey]);

                continue;
            }

            if (($sheetMeta['entity'] ?? '') === 'service_defaults') {
                $defaultsPreview = $this->previewServiceDefaults($source, $resolvedName, $limit);
                $sheets[] = array_merge($defaultsPreview, [
                    'sheet_key' => $sheetKey,
                    'sheet_name' => $resolvedName,
                    'entity' => 'service_defaults',
                ]);
                $totalRows += $defaultsPreview['total_data_rows'];
                if (! $defaultsPreview['valid']) {
                    $valid = false;
                    $errors = array_merge($errors, $defaultsPreview['errors']);
                }

                continue;
            }

            $entityKey = (string) ($sheetMeta['entity'] ?? '');
            $parsed = $this->reader->read($source, $resolvedName);
            if ($entityKey === 'services') {
                $this->workbookContext->absorbServiceCodesFromParsed($parsed, $this->reader);
            }
            $importer = $this->resolveImporter($entityKey, $workbookKey);
            $sheetPreview = $importer->previewParsed($parsed, $limit);

            $certification = $this->certifyHeaders($workbookKey, $sheetKey, $parsed['headers']);

            $importSummary = null;
            if ($entityKey === 'pincodes' && $importer instanceof PinCodeSpreadsheetImporter) {
                $importSummary = $importer->summarizeParsed($parsed);
            } elseif ($entityKey === 'pincodes' && method_exists($importer, 'summarizeParsed')) {
                $importSummary = $importer->summarizeParsed($parsed);
            }

            $sheets[] = [
                'sheet_key' => $sheetKey,
                'sheet_name' => $resolvedName,
                'entity' => $entityKey,
                'valid' => $sheetPreview['valid'],
                'errors' => $sheetPreview['errors'],
                'rows' => $sheetPreview['rows'],
                'total_data_rows' => $sheetPreview['total_data_rows'],
                'missing_columns' => $certification['missing'],
                'extra_columns' => $certification['extra'],
                'import_summary' => $importSummary,
            ];

            $totalRows += $sheetPreview['total_data_rows'];
            if (! $sheetPreview['valid']) {
                $valid = false;
                $errors = array_merge($errors, $sheetPreview['errors']);
            }
        }

        $warnings = [];
        foreach ($sheets as $sheet) {
            if (($sheet['missing_columns'] ?? []) !== []) {
                $warnings[] = __('Sheet :name missing optional template columns: :cols', [
                    'name' => $sheet['sheet_name'] ?? '',
                    'cols' => implode(', ', $sheet['missing_columns']),
                ]);
            }
        }

        return [
            'valid' => $valid,
            'workbook' => $workbookKey,
            'sheets' => $sheets,
            'errors' => $errors,
            'warnings' => $warnings,
            'total_data_rows' => $totalRows,
        ];
    }

    /**
     * @param  list<string>  $headers
     * @return array{missing: list<string>, extra: list<string>}
     */
    private function certifyHeaders(string $workbookKey, string $sheetKey, array $headers): array
    {
        $map = [
            'services' => [
                'categories' => 'categories',
                'services' => 'services',
                'subservices' => 'sub_services',
                'servicedefaults' => 'service_defaults',
            ],
            'pincodes' => [
                'pincodes' => 'pincodes',
                'geoenrichment' => 'geo_enrichment',
                'mappings' => 'mappings',
            ],
        ];

        $columnKey = $map[$workbookKey][$sheetKey] ?? null;
        if ($columnKey === null) {
            return ['missing' => [], 'extra' => []];
        }

        $expected = config("import_registry.template_columns.{$columnKey}", []);
        $present = array_flip($headers);
        $missing = array_values(array_filter($expected, fn (string $col): bool => ! isset($present[$col])));
        $extra = array_values(array_filter($headers, fn (string $col): bool => ! in_array($col, $expected, true)));

        return ['missing' => $missing, 'extra' => $extra];
    }

    /**
     * @return array{
     *     created: int, updated: int, skipped: int, failed: int,
     *     errors: list<string>, batch_ids: list<int>, post_sync: list<string>, sheets: list<array<string, mixed>>
     * }
     */
    /**
     * @param  array{force_upsert?: bool}  $options
     */
    public function commit(string $workbookKey, mixed $source, ?int $userId = null, ?string $filename = null, bool $runPostSync = true, array $options = []): array
    {
        $plan = $this->workbookPlan($workbookKey);
        if ($plan === null) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [__('Unknown workbook key: :key', ['key' => $workbookKey])],
                'batch_ids' => [],
                'post_sync' => [],
                'sheets' => [],
            ];
        }

        $sheetNames = $this->reader->sheetNames($source);
        $this->serviceDefaults->clear();
        $this->workbookContext->clear();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $batchIds = [];
        $sheetResults = [];
        $touchedEntities = [];

        foreach ($plan['sheet_order'] as $sheetKey) {
            $sheetMeta = $plan['sheets'][$sheetKey] ?? null;
            if ($sheetMeta === null) {
                continue;
            }

            if ($this->skipWorkbookSheet($workbookKey, $sheetKey, $sheetMeta)) {
                continue;
            }

            $resolvedName = $this->resolveSheetName($sheetNames, $sheetKey, $sheetMeta['aliases'] ?? []);
            if ($resolvedName === null) {
                if ($sheetMeta['optional'] ?? false) {
                    continue;
                }

                return [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'failed' => $failed + 1,
                    'errors' => array_merge($errors, [__('Missing required sheet: :sheet', ['sheet' => $sheetKey])]),
                    'batch_ids' => $batchIds,
                    'post_sync' => [],
                    'sheets' => $sheetResults,
                ];
            }

            if (($sheetMeta['entity'] ?? '') === 'service_defaults') {
                $loadResult = $this->loadServiceDefaults($source, $resolvedName);
                $sheetResults[] = [
                    'sheet_key' => $sheetKey,
                    'sheet_name' => $resolvedName,
                    'entity' => 'service_defaults',
                    'loaded' => $loadResult['loaded'],
                    'errors' => $loadResult['errors'],
                ];
                $errors = array_merge($errors, $loadResult['errors']);

                continue;
            }

            $entityKey = (string) ($sheetMeta['entity'] ?? '');
            $parsed = $this->reader->read($source, $resolvedName);
            $upsertPincodes = $workbookKey === 'pincodes'
                && $entityKey === 'pincodes'
                && app(\App\Services\Governance\MasterDataProtection::class)->pincodeWorkbookUpsertEnabled();

            $result = $this->pipeline->commit(
                $entityKey,
                $parsed,
                $userId,
                ($filename ? $filename.'#' : '').$resolvedName,
                false,
                ['upsert_pincodes' => $upsertPincodes]
            );

            $created += $result['created'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $failed += $result['failed'];
            $errors = array_merge($errors, $result['errors']);
            if ($result['batch_id'] !== null) {
                $batchIds[] = $result['batch_id'];
            }

            if ($result['created'] > 0 || $result['updated'] > 0) {
                $touchedEntities[] = $entityKey;
            }

            $sheetResults[] = [
                'sheet_key' => $sheetKey,
                'sheet_name' => $resolvedName,
                'entity' => $entityKey,
                'batch_id' => $result['batch_id'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ];
        }

        $postSync = [];
        if ($runPostSync && $touchedEntities !== []) {
            $postSync = $this->pipeline->postSyncForEntities(array_values(array_unique($touchedEntities)));
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'batch_ids' => $batchIds,
            'post_sync' => $postSync,
            'touched_entities' => array_values(array_unique($touchedEntities)),
            'sheets' => $sheetResults,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function workbookPlan(string $workbookKey): ?array
    {
        $plan = config("import_registry.workbooks.{$workbookKey}");
        if (! is_array($plan)) {
            return null;
        }

        return $plan;
    }

    /**
     * @param  list<string>  $available
     * @param  list<string>  $aliases
     */
    private function resolveSheetName(array $available, string $sheetKey, array $aliases): ?string
    {
        $candidates = array_merge([$sheetKey], $aliases);
        foreach ($candidates as $candidate) {
            foreach ($available as $name) {
                if (Str::lower($name) === Str::lower($candidate)) {
                    return $name;
                }
            }
        }

        return null;
    }

    private function resolveImporter(string $entityKey, string $workbookKey): EntityImporter
    {
        $importer = $this->registry->resolve($entityKey);

        if (
            $workbookKey === 'pincodes'
            && $entityKey === 'pincodes'
            && $importer instanceof PinCodeSpreadsheetImporter
            && app(\App\Services\Governance\MasterDataProtection::class)->pincodeWorkbookUpsertEnabled()
        ) {
            $importer->withUpsert(true);
        }

        return $importer;
    }

    /**
     * @return array{valid: bool, errors: list<string>, rows: list<array<string, mixed>>, total_data_rows: int}
     */
    private function previewServiceDefaults(mixed $source, string $sheetName, int $limit): array
    {
        try {
            $parsed = $this->reader->read($source, $sheetName);
        } catch (\Throwable $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()], 'rows' => [], 'total_data_rows' => 0];
        }

        $rows = [];
        $line = 1;
        foreach ($parsed['rows'] as $rawRow) {
            $line++;
            $mapped = $this->reader->mapRow($parsed['headers'], $rawRow);
            if ($this->rowIsBlank($mapped)) {
                continue;
            }
            if (count($rows) < $limit) {
                $code = trim((string) ($mapped['service_code'] ?? ''));
                $rows[] = [
                    'line' => $line,
                    'status' => 'defaults',
                    'key' => $code !== '' ? $code : '*',
                    'detail' => __('Service default template row.'),
                ];
            }
        }

        return [
            'valid' => true,
            'errors' => [],
            'rows' => $rows,
            'total_data_rows' => $parsed['total_data_rows'],
        ];
    }

    /**
     * @return array{loaded: int, errors: list<string>}
     */
    private function loadServiceDefaults(mixed $source, string $sheetName): array
    {
        try {
            $parsed = $this->reader->read($source, $sheetName);
        } catch (\Throwable $e) {
            return ['loaded' => 0, 'errors' => [$e->getMessage()]];
        }

        $loaded = 0;
        $line = 1;
        foreach ($parsed['rows'] as $rawRow) {
            $line++;
            $mapped = $this->reader->mapRow($parsed['headers'], $rawRow);
            if ($this->rowIsBlank($mapped)) {
                continue;
            }

            $code = trim((string) ($mapped['service_code'] ?? ''));
            if ($code === '') {
                $this->serviceDefaults->setGlobal($mapped);
            } else {
                $this->serviceDefaults->setForService($code, $mapped);
            }
            $loaded++;
        }

        return ['loaded' => $loaded, 'errors' => []];
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $sheetMeta
     */
    private function skipWorkbookSheet(string $workbookKey, string $sheetKey, array $sheetMeta): bool
    {
        if (! ($sheetMeta['system_managed'] ?? false)) {
            return false;
        }

        if ($workbookKey !== 'pincodes') {
            return false;
        }

        if (! config('import_registry.workflow.auto_map_service_pincodes', true)) {
            return false;
        }

        return (bool) config('import_registry.workbooks.pincodes.auto_map_service_pincodes', true);
    }

    /**
     * @return array<string, mixed>
     */
    private function systemManagedSheetPreview(string $sheetKey): array
    {
        return [
            'sheet_key' => $sheetKey,
            'sheet_name' => __('System auto-map'),
            'entity' => 'mappings',
            'valid' => true,
            'errors' => [],
            'rows' => [[
                'line' => '—',
                'status' => 'system_auto',
                'key' => __('Eligible pincodes link to published services after import.'),
                'detail' => null,
            ]],
            'total_data_rows' => 0,
            'missing_columns' => [],
            'extra_columns' => [],
        ];
    }
}
