<?php

namespace App\Observers;

use App\Models\PageElement;
use App\Models\PageSeo;
use App\Models\Service;
use App\Services\Growth\AiPulseService;
use App\Services\Growth\ContentSeoAutoFillService;
use Illuminate\Support\Facades\Schema;

class ServiceObserver
{
    public function __construct(
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly AiPulseService $aiPulseService,
    ) {}

    public function saved(Service $service): void
    {
        $this->contentSeoAutoFill->applyAndSyncService($service);
        $this->contentSeoAutoFill->refreshAggregateSignals();
        $this->aiPulseService->triggerAuditAfterPublish();
    }

    public function deleted(Service $service): void
    {
        $slugPath = 'services/'.ltrim((string) $service->service_code, '/');

        if (Schema::hasTable('page_seo')) {
            PageSeo::query()->where('page_slug', $slugPath)->delete();
        }

        if (Schema::hasTable('page_elements')) {
            PageElement::query()->where('page_slug', $slugPath)->delete();
        }

        $this->contentSeoAutoFill->refreshAggregateSignals();
        $this->aiPulseService->triggerAuditAfterPublish();
    }
}
