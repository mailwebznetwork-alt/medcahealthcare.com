<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Services\Growth\AiPulseService;
use App\Services\Growth\ContentSeoAutoFillService;
use Illuminate\Support\Facades\Schema;

class BlogObserver
{
    public function __construct(
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly AiPulseService $aiPulseService,
    ) {}

    public function saving(Blog $blog): void
    {
        $this->contentSeoAutoFill->applyToBlog($blog);
    }

    public function saved(Blog $blog): void
    {
        $this->contentSeoAutoFill->syncBlogGrowthArtifacts($blog);
        $this->contentSeoAutoFill->refreshAggregateSignals();
        $this->aiPulseService->triggerAuditAfterPublish();
    }

    public function deleted(Blog $blog): void
    {
        $slugPath = '/blog/'.ltrim((string) $blog->slug, '/');

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
