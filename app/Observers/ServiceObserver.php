<?php

namespace App\Observers;

use App\Models\PageElement;
use App\Models\PageSeo;
use App\Models\Service;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Growth\AiPulseService;
use App\Services\Growth\ContentSeoAutoFillService;
use App\Services\Operations\InternalLinkRefreshDispatcher;
use Illuminate\Support\Facades\Schema;

class ServiceObserver
{
    public function __construct(
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly AiPulseService $aiPulseService,
        private readonly InternalLinkRefreshDispatcher $linkRefreshDispatcher,
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly DownstreamArtifactPurger $purger,
    ) {}

    public function saved(Service $service): void
    {
        if ($this->deletionGuard->isServicePermanentlyDeleted($service->service_code)) {
            return;
        }

        $this->contentSeoAutoFill->applyAndSyncService($service);
        $this->contentSeoAutoFill->refreshAggregateSignals();
        $this->aiPulseService->triggerAuditAfterPublish();
        $this->linkRefreshDispatcher->dispatchForService($service->id);
    }

    public function deleted(Service $service): void
    {
        $this->linkRefreshDispatcher->dispatchForService($service->id, includePeers: true);
        $slugPath = 'services/'.ltrim((string) $service->service_code, '/');

        if (Schema::hasTable('page_seo')) {
            PageSeo::query()->where('page_slug', $slugPath)->delete();
        }

        if (Schema::hasTable('page_elements')) {
            PageElement::query()->where('page_slug', $slugPath)->delete();
        }

        $this->contentSeoAutoFill->refreshAggregateSignals();
        $this->aiPulseService->triggerAuditAfterPublish();
        $this->purger->purgeAfterCatalogEntityChange();
    }
}
