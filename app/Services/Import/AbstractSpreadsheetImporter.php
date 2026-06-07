<?php

namespace App\Services\Import;

use App\Services\Import\Contracts\EntityImporter;
use Illuminate\Support\Facades\DB;

abstract class AbstractSpreadsheetImporter implements EntityImporter
{
    public function __construct(
        protected readonly SpreadsheetReader $reader,
        protected readonly ImportBatchRecorder $recorder,
    ) {}

    /**
     * @return list<string>
     */
    abstract protected function requiredColumns(): array;

    /**
     * @return list<string>
     */
    protected function optionalColumns(): array
    {
        return [];
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array{status: string, detail: string|null, key: string|null}
     */
    abstract protected function previewRow(array $row, int $line): array;

    /**
     * @param  array<string, string|null>  $row
     * @return array{action: string, error: string|null}
     */
    abstract protected function importRow(array $row, int $line): array;

    protected function sheetName(): ?string
    {
        return null;
    }

    public function preview($source, int $limit = 25): array
    {
        $limit = $limit > 0 ? $limit : (int) config('import_registry.workflow.preview_row_limit', 25);

        try {
            $parsed = $this->reader->read($source, $this->sheetName());
        } catch (\Throwable $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()], 'rows' => [], 'total_data_rows' => 0];
        }

        return $this->previewParsed($parsed, $limit);
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}  $parsed
     * @return array{valid: bool, errors: list<string>, rows: list<array<string, mixed>>, total_data_rows: int}
     */
    public function previewParsed(array $parsed, int $limit = 25): array
    {
        $limit = $limit > 0 ? $limit : (int) config('import_registry.workflow.preview_row_limit', 25);

        $missing = $this->missingColumns($parsed['headers']);
        if ($missing !== []) {
            return [
                'valid' => false,
                'errors' => [__('Missing required columns: :cols', ['cols' => implode(', ', $missing)])],
                'rows' => [],
                'total_data_rows' => 0,
            ];
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
                $preview = $this->previewRow($mapped, $line);
                $rows[] = array_merge(['line' => $line], $preview);
            }
        }

        return [
            'valid' => true,
            'errors' => [],
            'rows' => $rows,
            'total_data_rows' => $parsed['total_data_rows'],
        ];
    }

    public function import($source): array
    {
        try {
            $parsed = $this->reader->read($source, $this->sheetName());
        } catch (\Throwable $e) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => [$e->getMessage()]];
        }

        return $this->importParsed($parsed);
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int}  $parsed
     * @return array{created: int, updated: int, skipped: int, failed: int, errors: list<string>}
     */
    public function importParsed(array $parsed): array
    {
        $batchSize = (int) config('import_registry.workflow.batch_size', 100);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $missing = $this->missingColumns($parsed['headers']);
        if ($missing !== []) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [__('Missing required columns: :cols', ['cols' => implode(', ', $missing)])],
            ];
        }

        $line = 1;
        $buffer = 0;

        DB::transaction(function () use ($parsed, &$line, &$created, &$updated, &$skipped, &$failed, &$errors, $batchSize, &$buffer): void {
            foreach ($parsed['rows'] as $rawRow) {
                $line++;
                $mapped = $this->reader->mapRow($parsed['headers'], $rawRow);
                if ($this->rowIsBlank($mapped)) {
                    continue;
                }

                try {
                    $result = $this->importRow($mapped, $line);
                    match ($result['action']) {
                        'created' => $created++,
                        'updated' => $updated++,
                        'skipped' => $skipped++,
                        default => $failed++,
                    };
                    if ($result['error'] !== null) {
                        $errors[] = "Line {$line}: {$result['error']}";
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Line {$line}: {$e->getMessage()}";
                }

                $buffer++;
                if ($buffer >= $batchSize) {
                    $buffer = 0;
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'failed', 'errors');
    }

    /**
     * @param  list<string>  $headers
     * @return list<string>
     */
    protected function missingColumns(array $headers): array
    {
        $normalized = array_flip($headers);
        $missing = [];
        foreach ($this->requiredColumns() as $col) {
            if (! isset($normalized[$col])) {
                $missing[] = $col;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    protected function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
