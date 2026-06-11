<?php

namespace App\Console\Commands;

use App\Models\ServiceCategory;
use App\Services\Operations\CategoryMasterOrchestrator;
use Illuminate\Console\Command;

class SyncCategoryMasterCommand extends Command
{
    protected $signature = 'medca:sync-category-master {category : Service category ID}';

    protected $description = 'Run category discovery + page sync outside the HTTP request';

    public function handle(CategoryMasterOrchestrator $orchestrator): int
    {
        $category = ServiceCategory::query()->find((int) $this->argument('category'));
        if ($category === null) {
            $this->error('Category not found.');

            return self::FAILURE;
        }

        $orchestrator->sync($category);
        $this->info("Category master sync complete: {$category->code}");

        return self::SUCCESS;
    }
}
