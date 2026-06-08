<?php

namespace App\Console\Commands;

use App\Services\Governance\DownstreamArtifactPurger;
use Illuminate\Console\Command;

class PurgeRegistryOrphansCommand extends Command
{
    protected $signature = 'medca:purge-registry-orphans';

    protected $description = 'Remove orphan registry rows and orphan catalog CMS pages (database is authoritative).';

    public function handle(DownstreamArtifactPurger $purger): int
    {
        $result = $purger->purgeAllCatalogOrphans();

        $this->info("Removed {$result['registry_removed']} orphan registry row(s).");
        $this->info("Removed {$result['location_pages_removed']} orphan location page(s).");
        $this->info("Removed {$result['service_pages_removed']} orphan service page(s).");
        $this->info("Removed {$result['sub_service_pages_removed']} orphan sub-service page(s).");
        $this->info("Removed {$result['category_pages_removed']} orphan category page(s).");

        foreach ($result['issues'] as $issue) {
            $this->line("  • {$issue}");
        }

        return self::SUCCESS;
    }
}
