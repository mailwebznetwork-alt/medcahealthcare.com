<?php

namespace App\Console\Commands;

use App\Services\Import\ImportPipeline;
use Illuminate\Console\Command;

class RunImportPostSyncCommand extends Command
{
    protected $signature = 'medca:import-post-sync {entities* : Entity keys touched by import (e.g. categories services)}';

    protected $description = 'Run deferred import post-sync outside the HTTP request lifecycle';

    public function handle(ImportPipeline $pipeline): int
    {
        @set_time_limit(0);

        $entities = array_values(array_filter($this->argument('entities')));
        if ($entities === []) {
            $this->warn('No entities provided.');

            return self::FAILURE;
        }

        $ran = $pipeline->postSyncForEntities($entities);
        $this->info('Post-sync commands: '.implode(', ', $ran));

        return self::SUCCESS;
    }
}
