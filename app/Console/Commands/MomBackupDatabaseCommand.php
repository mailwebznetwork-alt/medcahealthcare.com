<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MomBackupDatabaseCommand extends Command
{
    protected $signature = 'mom:backup-database';

    protected $description = 'Copy SQLite DB to storage/app/backups (PDF Settings — Backup & Restore baseline).';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $default = config('database.default');

        if ($default !== 'sqlite') {
            $this->warn(__('Automatic dump for :driver is not bundled — use mysqldump/pg_dump on the server or add a backup package.', ['driver' => (string) $default]));

            return self::FAILURE;
        }

        $src = database_path('database.sqlite');
        if (! File::exists($src)) {
            $this->error(__('SQLite database file not found at :path.', ['path' => $src]));

            return self::FAILURE;
        }

        $dest = $dir.'/database-'.now()->format('Y-m-d-His').'.sqlite';
        File::copy($src, $dest);
        $this->info(__('Backup written to :path', ['path' => $dest]));

        return self::SUCCESS;
    }
}
