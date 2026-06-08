<?php

namespace App\Observers;

use App\Models\ServiceCategory;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Operations\CategoryMasterOrchestrator;

class ServiceCategoryObserver
{
    public function __construct(
        private readonly CategoryMasterOrchestrator $orchestrator,
        private readonly UniversalPageRegistry $pageRegistry,
    ) {}

    public function saved(ServiceCategory $category): void
    {
        if (config('services_master.category_discovery.auto_sync', true)) {
            $this->orchestrator->sync($category);
        } else {
            $this->pageRegistry->upsertCategoryEntry($category->fresh());
        }
    }

    public function deleting(ServiceCategory $category): void
    {
        $this->orchestrator->teardown($category);
    }
}
