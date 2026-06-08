<?php

namespace App\Console\Commands;

use App\Services\Governance\DownstreamArtifactPurger;
use Illuminate\Console\Command;

class PurgeRegistryOrphansCommand extends Command
{
    protected $signature = 'medca:purge-registry-orphans';

    protected $description = 'Remove registry entries whose database entities no longer exist (database is authoritative).';

    public function handle(DownstreamArtifactPurger $purger): int
    {
        $result = $purger->purgeRegistryOrphans();

        $this->info("Removed {$result['registry_removed']} orphan registry row(s).");

        foreach ($result['issues'] as $issue) {
            $this->line("  • {$issue}");
        }

        return self::SUCCESS;
    }
}
