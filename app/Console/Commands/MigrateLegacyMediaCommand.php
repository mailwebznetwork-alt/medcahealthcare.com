<?php

namespace App\Console\Commands;

use App\Services\Media\LegacyMediaMigrator;
use Illuminate\Console\Command;

class MigrateLegacyMediaCommand extends Command
{
    protected $signature = 'media:migrate-legacy {--dir=* : Additional directories under storage/app/public to scan}';

    protected $description = 'Import legacy storage paths into the centralized Media Library.';

    public function handle(LegacyMediaMigrator $migrator): int
    {
        $dirs = $this->option('dir');
        $extra = is_array($dirs) ? array_filter(array_map('strval', $dirs)) : [];
        $scan = $extra !== [] ? $extra : [];

        $report = $migrator->migrate($scan);

        $this->info(__('Legacy media migration complete.'));
        $this->table(
            ['Metric', 'Count'],
            [
                ['Imported', $report['imported']],
                ['Skipped', $report['skipped']],
                ['Duplicate (hash)', $report['duplicate']],
                ['Failed', $report['failed']],
            ]
        );

        if ($report['errors'] !== []) {
            $this->warn(__('Failures:'));
            foreach (array_slice($report['errors'], 0, 20) as $error) {
                $this->line(' - '.$error);
            }
        }

        return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
