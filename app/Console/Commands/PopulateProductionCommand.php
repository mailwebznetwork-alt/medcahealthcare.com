<?php

namespace App\Console\Commands;

use App\Services\Launch\ProductionPopulationService;
use Illuminate\Console\Command;

class PopulateProductionCommand extends Command
{
    protected $signature = 'medca:populate-production {--skip-media-seeder : Skip MedcaLaunchServicesSeeder image enrichment}';

    protected $description = 'Import production catalog data and run post-sync orchestration';

    public function handle(ProductionPopulationService $population): int
    {
        $this->info('=== MarkOnMinds production data population ===');

        $log = $population->populate(! $this->option('skip-media-seeder'));

        foreach ($log['steps'] ?? [] as $step) {
            $this->line("  ✓ {$step}");
        }

        foreach ($log['imports'] ?? [] as $entity => $result) {
            $this->components->info("{$entity}: +{$result['created']} created, ~{$result['updated']} updated, {$result['skipped']} skipped");
        }

        $this->newLine();
        $this->comment('Run medca:production-launch-report for validation scores.');

        return self::SUCCESS;
    }
}
