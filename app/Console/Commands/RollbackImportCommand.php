<?php

namespace App\Console\Commands;

use App\Services\Import\ImportRollbackService;
use Illuminate\Console\Command;

class RollbackImportCommand extends Command
{
    protected $signature = 'medca:rollback-import {batch : Import batch ID}';

    protected $description = 'Rollback a committed import batch';

    public function handle(ImportRollbackService $rollback): int
    {
        $result = $rollback->rollback((int) $this->argument('batch'));

        if (! $result['success'] && $result['reverted'] === 0) {
            foreach ($result['errors'] as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info("Reverted {$result['reverted']} entries.");
        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }
}
