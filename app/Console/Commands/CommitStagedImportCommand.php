<?php

namespace App\Console\Commands;

use App\Services\Import\StagedImportCommitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CommitStagedImportCommand extends Command
{
    protected $signature = 'medca:commit-staged-import {jobId : UUID of the queued import job}';

    protected $description = 'Commit a staged bulk import in the background (CLI has no nginx timeout)';

    public function handle(StagedImportCommitService $committer): int
    {
        $jobId = (string) $this->argument('jobId');
        if ($jobId === '' || ! preg_match('/^[a-f0-9-]{36}$/i', $jobId)) {
            $this->error('Invalid job ID.');

            return self::FAILURE;
        }

        $manifestPath = "temp/import-jobs/{$jobId}.json";
        if (! Storage::disk('local')->exists($manifestPath)) {
            $this->error("Import job not found: {$jobId}");

            return self::FAILURE;
        }

        $manifest = json_decode(Storage::disk('local')->get($manifestPath), true);
        if (! is_array($manifest) || ! is_array($manifest['staging'] ?? null)) {
            Storage::disk('local')->delete($manifestPath);
            $this->error('Import job manifest is invalid.');

            return self::FAILURE;
        }

        $staging = $manifest['staging'];
        $userId = isset($manifest['user_id']) ? (int) $manifest['user_id'] : null;

        $this->info("Committing staged import {$jobId}…");

        try {
            $result = $committer->commit($staging, $userId ?: null);
        } catch (\Throwable $e) {
            Storage::disk('local')->put("temp/import-jobs/{$jobId}-result.json", json_encode([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR));
            Storage::disk('local')->delete($manifestPath);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (isset($staging['path']) && is_string($staging['path'])) {
            Storage::disk('local')->delete($staging['path']);
        }

        Storage::disk('local')->put("temp/import-jobs/{$jobId}-result.json", json_encode([
            'status' => ($result['failed'] ?? 0) > 0 ? 'completed_with_warnings' : 'completed',
            'result' => $result,
            'finished_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->delete($manifestPath);

        $this->info(sprintf(
            'Done: created=%d updated=%d skipped=%d failed=%d',
            (int) ($result['created'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            (int) ($result['failed'] ?? 0),
        ));

        return ($result['failed'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
