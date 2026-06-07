<?php

namespace App\Console\Commands;

use App\Models\ServiceCategory;
use App\Services\Operations\CategoryMasterOrchestrator;
use Illuminate\Console\Command;

class SyncCategoryPagesCommand extends Command
{
    protected $signature = 'medca:sync-category-pages {--code= : Single category code}';

    protected $description = 'Generate or update category discovery CMS pages from database';

    public function handle(CategoryMasterOrchestrator $orchestrator): int
    {
        $query = ServiceCategory::query()->active()->ordered();
        if ($code = $this->option('code')) {
            $query->where('code', $code);
        }

        $count = 0;
        $query->each(function (ServiceCategory $category) use ($orchestrator, &$count): void {
            $orchestrator->sync($category);
            $count++;
        });

        $this->info("Synced {$count} category page(s).");

        return self::SUCCESS;
    }
}
