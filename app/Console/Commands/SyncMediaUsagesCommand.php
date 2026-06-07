<?php

namespace App\Console\Commands;

use App\Services\Media\MediaUsagesIndexer;
use Illuminate\Console\Command;

class SyncMediaUsagesCommand extends Command
{
    protected $signature = 'media:sync-usages';

    protected $description = 'Rebuild media usage references from services, blocks, and pages.';

    public function handle(MediaUsagesIndexer $indexer): int
    {
        $count = $indexer->reindexAll();
        $this->info(__('Media usage index rebuilt (:count references).', ['count' => $count]));

        return self::SUCCESS;
    }
}
