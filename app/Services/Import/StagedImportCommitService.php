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
            $result = $this->workbooks->commit(
                (string) $staging['workbook'],
                $absolute,
                $userId,
                $staging['original_filename'] ?? null,
                false,
            );

            $touchedEntities = $result['touched_entities'] ?? [];
            if ($touchedEntities !== []) {
                $this->dispatchImportPostSync($touchedEntities);
                $result['post_sync_pending'] = true;
            }

            return $result;
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

        $result = $this->pipeline->commit(
            (string) $staging['entity'],
            $absolute,
            $userId,
            $staging['original_filename'] ?? null,
            false,
        );

        $entity = (string) $staging['entity'];
        if (($result['created'] ?? 0) > 0 || ($result['updated'] ?? 0) > 0) {
            $this->dispatchImportPostSync([$entity]);
            $result['post_sync_pending'] = true;
        }

        return $result;
    }

    /**
     * @param  list<string>  $entities
     */
    private function dispatchImportPostSync(array $entities): void
    {
        $entities = array_values(array_filter($entities));
        if ($entities === []) {
            return;
        }

        $args = implode(' ', array_map('escapeshellarg', $entities));
        $command = sprintf(
            'cd %s && php artisan medca:import-post-sync %s >> %s 2>&1 &',
            escapeshellarg(base_path()),
            $args,
            escapeshellarg(storage_path('logs/import-post-sync.log'))
        );

        exec($command);
    }
}
