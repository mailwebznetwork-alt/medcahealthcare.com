<?php

namespace App\Services\Operations;

use App\Models\SubService;

class SubServiceMasterOrchestrator
{
    public function __construct(
        private readonly SubServicePageProvisioner $pageProvisioner,
    ) {}

    public function sync(SubService $sub): void
    {
        if (! config('phase2_discovery.auto_sync_sub_service_pages', true)) {
            return;
        }

        $this->pageProvisioner->syncFromSubService($sub->fresh(['seo', 'faqs', 'schema', 'service']));
    }

    public function teardown(SubService $sub): void
    {
        $this->pageProvisioner->deleteOwnedPage($sub);
    }
}
