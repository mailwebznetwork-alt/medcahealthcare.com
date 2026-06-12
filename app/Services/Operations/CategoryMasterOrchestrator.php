<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;

class CategoryMasterOrchestrator
{
    public function __construct(
        private readonly CategoryDiscoveryEngine $discoveryEngine,
        private readonly CategoryPageProvisioner $pageProvisioner,
    ) {}

    public function sync(ServiceCategory $category): void
    {
        $this->discoveryEngine->sync($category);

        if (! config('phase2_discovery.auto_sync_category_pages', true)) {
            return;
        }

        if (! ServiceGeneratedPageEligibility::categoryMayHavePages($category)) {
            $this->pageProvisioner->deleteOwnedPage($category);

            return;
        }

        $this->pageProvisioner->syncFromCategory($category->fresh(['seo', 'faqs', 'schema']));
    }

    public function teardown(ServiceCategory $category): void
    {
        $this->pageProvisioner->deleteOwnedPage($category);
    }
}
