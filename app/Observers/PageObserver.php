<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Services\Growth\AiPulseService;
use App\Services\Growth\ContentSeoAutoFillService;
use Illuminate\Support\Facades\Schema;

class PageObserver
{
    public function __construct(
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly AiPulseService $aiPulseService,
    ) {}

    public function saving(Page $page): void
    {
        $this->contentSeoAutoFill->applyToPage($page);
    }

    public function saved(Page $page): void
    {
        $this->contentSeoAutoFill->syncPageGrowthArtifacts($page);
        $this->contentSeoAutoFill->refreshAggregateSignals();
        $this->aiPulseService->triggerAuditAfterPublish();
    }

    public function deleted(Page $page): void
    {
        $slugPath = '/p/'.ltrim((string) $page->slug, '/');

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
