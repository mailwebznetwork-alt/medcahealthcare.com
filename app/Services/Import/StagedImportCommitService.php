<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Storage;

/**
 * Runs a staged bulk-import commit (workbook or single entity).
 */
final class StagedImportCommitService
{
    public function __construct(
        private readonly ImportPipeline $pipeline,
        private readonly WorkbookImportOrchestrator $workbooks,
    ) {}

    /**
     * @param  array<string, mixed>  $staging
     * @return array<string, mixed>
     */
    public function commit(array $staging, ?int $userId): array
    {
        $timeLimit = (int) config('import_registry.workflow.commit_time_limit', 600);
        @set_time_limit($timeLimit);
        @ini_set('max_execution_time', (string) $timeLimit);
        @ini_set('memory_limit', (string) config('import_registry.workflow.commit_memory_limit', '512M'));

        if (empty($staging['path']) || ! is_string($staging['path'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => [__('Missing staged import file path.')],
            ];
        }

        $absolute = Storage::disk('local')->path($staging['path']);
        if (! is_readable($absolute)) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => [__('Staged file no longer available.')],
            ];
        }

        $mode = $staging['mode'] ?? 'entity';
        if ($mode === 'workbook' && ! empty($staging['workbook'])) {
            return $this->workbooks->commit(
                (string) $staging['workbook'],
                $absolute,
                $userId,
                $staging['original_filename'] ?? null,
                true,
            );
        }

        if (empty($staging['entity'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => [__('Missing import entity.')],
            ];
        }

        return $this->pipeline->commit(
            (string) $staging['entity'],
            $absolute,
            $userId,
            $staging['original_filename'] ?? null,
            true,
        );
    }
}
