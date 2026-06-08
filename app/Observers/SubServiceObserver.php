<?php

namespace App\Observers;

use App\Models\SubService;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\SubServiceCreationGuard;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Operations\SubServiceMasterOrchestrator;

class SubServiceObserver
{
    public function __construct(
        private readonly SubServiceMasterOrchestrator $orchestrator,
        private readonly UniversalPageRegistry $pageRegistry,
        private readonly AdminDeletionGuard $deletionGuard,
    ) {}

    public function saved(SubService $sub): void
    {
        $naturalKey = SubServiceCreationGuard::naturalKeyFromModel($sub);
        if ($this->deletionGuard->isSubServicePermanentlyDeleted($naturalKey)) {
            return;
        }

        if (config('phase2_discovery.auto_sync_sub_service_pages', true)) {
            $this->orchestrator->sync($sub);
        } else {
            $this->pageRegistry->upsertSubServiceEntry($sub->fresh());
        }
    }

    public function deleting(SubService $sub): void
    {
        $this->orchestrator->teardown($sub);
    }
}
