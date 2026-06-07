<?php

namespace App\Observers;

use App\Models\SubService;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Operations\SubServiceMasterOrchestrator;

class SubServiceObserver
{
    public function __construct(
        private readonly SubServiceMasterOrchestrator $orchestrator,
        private readonly UniversalPageRegistry $pageRegistry,
    ) {}

    public function saved(SubService $sub): void
    {
        if (config('phase2_discovery.auto_sync_sub_service_pages', true)) {
            $this->orchestrator->sync($sub);
        } else {
            $this->pageRegistry->upsertSubServiceEntry($sub->fresh());
        }
    }

    public function deleted(SubService $sub): void
    {
        \App\Models\PageRegistry::query()
            ->where('entity_type', 'sub_service')
            ->where('entity_id', $sub->id)
            ->delete();
    }
}
