<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupHealthReportCommand extends Command
{
    protected $signature = 'medca:backup-health-report {--output= : Markdown path}';

    protected $description = 'Report backup and restore-point health';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        $restoreDir = storage_path('app/restore-points');

        $backups = File::isDirectory($backupDir)
            ? collect(File::files($backupDir))->sortByDesc(fn ($f) => $f->getMTime())->values()
            : collect();

        $restorePoints = File::isDirectory($restoreDir)
            ? collect(File::directories($restoreDir))->sortByDesc(fn ($d) => filemtime($d))->values()
            : collect();

        $latestBackup = $backups->first();
        $latestRestore = $restorePoints->first();

        $lines = [
            '# Backup Health Report',
            '',
            'Generated: '.now()->timezone('Asia/Kolkata')->toDateTimeString().' IST',
            '',
            '## Database backups (`storage/app/backups`)',
            '- Count: '.$backups->count(),
            '- Latest: '.($latestBackup ? $latestBackup->getFilename().' ('.date('Y-m-d H:i', $latestBackup->getMTime()).')' : 'none'),
            '',
            '## Restore points (`storage/app/restore-points`)',
            '- Count: '.$restorePoints->count(),
            '- Latest: '.($latestRestore ? basename($latestRestore).' ('.date('Y-m-d H:i', filemtime($latestRestore)).')' : 'none'),
            '',
            '## Recommendations',
        ];

        if ($backups->isEmpty()) {
            $lines[] = '- Run `mom:backup-database` or enable scheduled backup in settings.';
        }
        if ($restorePoints->isEmpty()) {
            $lines[] = '- Create a restore point after major catalog changes.';
        }

        $markdown = implode("\n", $lines)."\n";
        $output = $this->option('output') ?: base_path('docs/BACKUP-HEALTH-REPORT.md');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, $markdown);

        $this->info("Backup health report: {$output}");

        return self::SUCCESS;
    }
}
