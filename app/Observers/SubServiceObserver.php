<?php

namespace App\Observers;

use App\Models\SubService;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Import\ImportSideEffectsGate;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\SubServiceCreationGuard;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Operations\SubServiceMasterOrchestrator;
use App\Services\Public\CatalogPublicCache;

class SubServiceObserver
{
    public function __construct(
        private readonly SubServiceMasterOrchestrator $orchestrator,
        private readonly UniversalPageRegistry $pageRegistry,
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly DownstreamArtifactPurger $purger,
        private readonly CatalogPublicCache $publicCache,
    ) {}

    public function saved(SubService $sub): void
    {
        if (app(ImportSideEffectsGate::class)->suppressed()) {
            return;
        }

        $naturalKey = SubServiceCreationGuard::naturalKeyFromModel($sub);
        if ($this->deletionGuard->isSubServicePermanentlyDeleted($naturalKey)) {
            return;
        }

        if (config('phase2_discovery.auto_sync_sub_service_pages', true)) {
            $this->orchestrator->sync($sub);
        } else {
            $this->pageRegistry->upsertSubServiceEntry($sub->fresh());
        }

        $this->publicCache->forgetForSubService($sub);
    }

    public function deleting(SubService $sub): void
    {
        $this->orchestrator->teardown($sub);
    }

    public function deleted(SubService $sub): void
    {
        $this->publicCache->forgetForSubService($sub);
        $this->purger->purgeAfterCatalogEntityChange();
    }
}
