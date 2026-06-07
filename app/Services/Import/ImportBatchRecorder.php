<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Models\ImportBatchEntry;

/**
 * Records import actions for audit and rollback.
 */
class ImportBatchRecorder
{
    private ?ImportBatch $batch = null;

    public function start(string $entityKey, ?int $userId = null, ?string $filename = null): ImportBatch
    {
        $this->batch = ImportBatch::query()->create([
            'entity_key' => $entityKey,
            'user_id' => $userId,
            'original_filename' => $filename,
            'status' => 'in_progress',
        ]);

        return $this->batch;
    }

    public function current(): ?ImportBatch
    {
        return $this->batch;
    }

    /**
     * @param  array<string, mixed>|null  $previousState
     */
    public function record(string $action, string $entityType, ?int $entityId, ?array $previousState = null, ?int $line = null): void
    {
        if ($this->batch === null) {
            return;
        }

        ImportBatchEntry::query()->create([
            'import_batch_id' => $this->batch->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'previous_state' => $previousState,
            'line_number' => $line,
        ]);
    }

    /**
     * @param  array{created: int, updated: int, skipped: int, failed: int, errors: list<string>}  $result
     */
    public function finish(array $result): ImportBatch
    {
        if ($this->batch === null) {
            throw new \RuntimeException('No import batch started.');
        }

        $errors = $result['errors'] ?? [];
        $status = ($result['failed'] ?? 0) > 0 || $errors !== []
            ? 'completed_with_warnings'
            : 'committed';

        $this->batch->update([
            'status' => $status,
            'rows_created' => (int) ($result['created'] ?? 0),
            'rows_updated' => (int) ($result['updated'] ?? 0),
            'rows_skipped' => (int) ($result['skipped'] ?? 0),
            'rows_failed' => (int) ($result['failed'] ?? 0),
            'error_summary' => $errors !== [] ? implode("\n", array_slice($errors, 0, 50)) : null,
            'committed_at' => now(),
        ]);

        return $this->batch->fresh();
    }
}
