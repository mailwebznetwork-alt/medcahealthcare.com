<?php

namespace App\Console\Commands;

use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Pages\MarketingPageBlockPatcher;
use Illuminate\Console\Command;

class RepairNearYouBlocksCommand extends Command
{
    protected $signature = 'site-architect:repair-near-you
                            {--no-sync : Skip blocks:sync for near-you templates}';

    protected $description = 'Sync Near You blocks to the registry and ensure home/locations page tokens exist';

    public function handle(BlockTemplateSyncService $sync, MarketingPageBlockPatcher $patcher): int
    {
        if (! $this->option('no-sync')) {
            $result = $sync->sync(
                slugs: ['near-you-home', 'near-you-locations'],
                backup: true,
            );
            $this->info('Synced: '.implode(', ', $result['synced'] ?: ['none']));
        }

        $patched = $patcher->ensureRequiredNearYouBlocks();
        $this->info('Home page patched: '.($patched['home'] ? 'yes' : 'already present'));
        $this->info('Locations page patched: '.($patched['locations'] ? 'yes' : 'already present'));

        $this->components->success('Near You blocks are ready. Edit copy in Blocks Studio → near-you-home / near-you-locations.');

        return self::SUCCESS;
    }
}
