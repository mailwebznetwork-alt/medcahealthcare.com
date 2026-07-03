<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MasterSpec\MasterContentCompletionService;
use Illuminate\Console\Command;

class CompleteMasterContentCommand extends Command
{
    protected $signature = 'medca:complete-master-content
                            {--no-schema-sync : Skip catalog schema and page orchestration sync}
                            {--dry-run : Report counts only without persisting (not fully supported — use content-health report)}';

    protected $description = 'Complete empty catalog, SEO, AEO, and CMS content fields (excludes Blog and Location pages)';

    public function handle(MasterContentCompletionService $completion): int
    {
        if ($this->option('dry-run')) {
            $this->warn('Dry-run not implemented — running medca:content-health-report is recommended for preview.');
        }

        $this->info('Starting Medca master content completion…');

        $stats = $completion->run(syncSchemas: ! $this->option('no-schema-sync'));

        $this->table(
            ['Area', 'Updated'],
            collect($stats)
                ->except('messages')
                ->map(fn (mixed $value, string $key): array => [$key, is_array($value) ? implode('; ', $value) : (string) $value])
                ->values()
                ->all()
        );

        foreach ($stats['messages'] ?? [] as $message) {
            $this->line(' - '.$message);
        }

        $this->newLine();
        $this->info('Done. Verify with: php artisan medca:content-health-report');

        return self::SUCCESS;
    }
}
