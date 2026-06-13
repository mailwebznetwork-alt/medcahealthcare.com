<?php

namespace App\Observers;

use App\Models\ServiceCategory;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Import\ImportSideEffectsGate;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Operations\CategoryMasterOrchestrator;
use App\Services\Public\CatalogPublicCache;

class ServiceCategoryObserver
{
    public function __construct(
        private readonly CategoryMasterOrchestrator $orchestrator,
        private readonly UniversalPageRegistry $pageRegistry,
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly DownstreamArtifactPurger $purger,
        private readonly CatalogPublicCache $publicCache,
    ) {}

    public function saved(ServiceCategory $category): void
    {
        if (app(ImportSideEffectsGate::class)->suppressed()) {
            return;
        }

        if ($category->trashed() || $this->deletionGuard->isCategoryPermanentlyDeleted($category->code)) {
            return;
        }

        if (config('services_master.category_discovery.auto_sync', true)) {
            $this->orchestrator->sync($category);
        } else {
            $this->pageRegistry->upsertCategoryEntry($category->fresh());
        }

        $this->publicCache->forgetForCategory($category);
    }

    public function deleting(ServiceCategory $category): void
    {
        $this->orchestrator->teardown($category);
    }

    public function deleted(ServiceCategory $category): void
    {
        $this->publicCache->forgetForCategory($category);
        $this->purger->purgeAfterCatalogEntityChange();
    }
}
