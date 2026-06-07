<?php

namespace App\Console\Commands;

use App\Models\SubService;
use App\Services\Operations\SubServiceMasterOrchestrator;
use Illuminate\Console\Command;

class SyncSubServicePagesCommand extends Command
{
    protected $signature = 'medca:sync-sub-service-pages {--service= : Parent service code}';

    protected $description = 'Generate or update sub-service CMS pages from database';

    public function handle(SubServiceMasterOrchestrator $orchestrator): int
    {
        $query = SubService::query()->where('is_active', true);
        if ($code = $this->option('service')) {
            $query->whereHas('service', fn ($q) => $q->where('service_code', $code));
        }

        $count = 0;
        $query->each(function (SubService $sub) use ($orchestrator, &$count): void {
            $orchestrator->sync($sub);
            $count++;
        });

        $this->info("Synced {$count} sub-service page(s).");

        return self::SUCCESS;
    }
}
