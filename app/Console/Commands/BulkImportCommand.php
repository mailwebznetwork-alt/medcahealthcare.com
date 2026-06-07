<?php

namespace App\Console\Commands;

use App\Services\Import\ImportPipeline;
use App\Services\Import\ImportRegistry;
use App\Services\Import\WorkbookImportOrchestrator;
use Illuminate\Console\Command;

class BulkImportCommand extends Command
{
    protected $signature = 'medca:import
        {target : Entity key or workbook key (services, pincodes, categories, …)}
        {file : Path to CSV/XLS/XLSX file}
        {--preview : Preview only, no commit}
        {--no-post-sync : Skip post-import sync commands}
        {--workbook : Treat target as master workbook key}';

    protected $description = 'Bulk import catalog data via the import pipeline';

    public function handle(
        ImportRegistry $registry,
        ImportPipeline $pipeline,
        WorkbookImportOrchestrator $workbooks,
    ): int {
        $target = $this->argument('target');
        $file = $this->argument('file');

        if (! is_readable($file)) {
            $this->error("File not readable: {$file}");

            return self::FAILURE;
        }

        $workbookKey = $this->option('workbook')
            ? $target
            : ($workbooks->detectWorkbookKey(basename($file)) ?? (array_key_exists($target, config('import_registry.workbooks', [])) ? $target : null));

        if ($workbookKey !== null) {
            return $this->handleWorkbook($workbooks, $workbookKey, $file);
        }

        if (! in_array($target, $registry->registeredEntities(), true)) {
            $this->error("Unknown entity [{$target}]. Registered: ".implode(', ', $registry->registeredEntities()));

            return self::FAILURE;
        }

        if ($this->option('preview')) {
            $preview = $pipeline->preview($target, $file);
            $this->table(['valid', 'total_rows', 'errors'], [[
                $preview['valid'] ? 'yes' : 'no',
                $preview['total_data_rows'],
                implode('; ', $preview['errors']),
            ]]);
            $this->table(['line', 'status', 'key', 'detail'], array_map(
                fn ($r) => [$r['line'] ?? '-', $r['status'] ?? '-', $r['key'] ?? '-', $r['detail'] ?? ''],
                array_slice($preview['rows'], 0, 10)
            ));

            return $preview['valid'] ? self::SUCCESS : self::FAILURE;
        }

        $result = $pipeline->commit(
            $target,
            $file,
            null,
            basename($file),
            ! $this->option('no-post-sync')
        );

        $this->info("Import batch #{$result['batch_id']}: created={$result['created']}, updated={$result['updated']}, skipped={$result['skipped']}, failed={$result['failed']}");
        if ($result['post_sync'] !== []) {
            $this->line('Post-sync: '.implode(', ', $result['post_sync']));
        }
        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return ($result['failed'] ?? 0) === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function handleWorkbook(WorkbookImportOrchestrator $workbooks, string $workbookKey, string $file): int
    {
        if ($this->option('preview')) {
            $preview = $workbooks->preview($workbookKey, $file);
            $this->table(['valid', 'workbook', 'total_rows', 'errors'], [[
                $preview['valid'] ? 'yes' : 'no',
                $preview['workbook'] ?? $workbookKey,
                $preview['total_data_rows'],
                implode('; ', $preview['errors']),
            ]]);
            foreach ($preview['sheets'] as $sheet) {
                $this->line(($sheet['sheet_name'] ?? '').' ['.($sheet['entity'] ?? '').'] — '.($sheet['total_data_rows'] ?? 0).' rows');
            }

            return $preview['valid'] ? self::SUCCESS : self::FAILURE;
        }

        $result = $workbooks->commit(
            $workbookKey,
            $file,
            null,
            basename($file),
            ! $this->option('no-post-sync')
        );

        $this->info('Workbook import: created='.$result['created'].', updated='.$result['updated'].', skipped='.$result['skipped'].', failed='.$result['failed']);
        if ($result['batch_ids'] !== []) {
            $this->line('Batches: '.implode(', ', array_map(fn ($id) => '#'.$id, $result['batch_ids'])));
        }
        if ($result['post_sync'] !== []) {
            $this->line('Post-sync: '.implode(', ', $result['post_sync']));
        }
        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return ($result['failed'] ?? 0) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
