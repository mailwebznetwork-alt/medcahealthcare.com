<?php

namespace App\Console\Commands;

use App\Services\Operations\ServiceDetailPageProvisioner;
use Illuminate\Console\Command;

class SyncServiceDetailPagesCommand extends Command
{
    protected $signature = 'services:sync-detail-pages {--only-missing : Only provision services without a linked detail page}';

    protected $description = 'Create or update Site Architect pages for every service (slug service-{code})';

    public function handle(ServiceDetailPageProvisioner $provisioner): int
    {
        $result = $provisioner->provisionAll(
            onlyWithoutLinkedPage: (bool) $this->option('only-missing')
        );

        $this->info(sprintf(
            'Done. %d created, %d linked/updated, %d skipped.',
            $result['created'],
            $result['linked'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
