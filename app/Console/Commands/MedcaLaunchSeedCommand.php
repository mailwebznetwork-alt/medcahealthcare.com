<?php

namespace App\Console\Commands;

use Database\Seeders\MedcaLaunchSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MedcaLaunchSeedCommand extends Command
{
    protected $signature = 'medca:launch-seed
                            {--skip-storage-link : Do not run storage:link}
                            {--verify : Run launch verification tests after seeding}';

    protected $description = 'Seed Medca services, marketing pages, pincodes, and global contact content for production launch';

    public function handle(): int
    {
        $backupDir = '/var/backups/medca-launch-'.now()->format('Ymd-His');
        $this->components->info("Recommended backup before launch seed: {$backupDir}");
        $this->components->warn('Create a DB/files backup in ops before running on production.');

        if (! $this->option('skip-storage-link')) {
            Artisan::call('storage:link');
            $this->line(trim(Artisan::output()));
        }

        $this->call('db:seed', ['--class' => MedcaLaunchSeeder::class, '--force' => true]);

        Artisan::call('config:clear');
        Artisan::call('view:clear');

        $this->components->success('Medca launch data seeded.');

        if ($this->option('verify')) {
            $exitCode = $this->callSilent('test', [
                '--filter' => 'MedcaLaunch',
            ]);

            return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }
}
