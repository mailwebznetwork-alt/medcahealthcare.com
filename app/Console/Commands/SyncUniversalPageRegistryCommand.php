<?php

namespace App\Console\Commands;

use App\Services\Governance\UniversalPageRegistry;
use Illuminate\Console\Command;

class SyncUniversalPageRegistryCommand extends Command
{
    protected $signature = 'medca:sync-page-registry';

    protected $description = 'Sync universal page registry from pages and catalog entities';

    public function handle(UniversalPageRegistry $registry): int
    {
        $counts = $registry->syncAll();

        $this->table(
            ['Metric', 'Count'],
            collect($counts)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        $this->info('Universal page registry synced.');

        return self::SUCCESS;
    }
}
