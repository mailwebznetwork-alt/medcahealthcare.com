<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Services\Growth\ContentSeoAutoFillService;
use App\Services\Growth\SitemapRegenerationDispatcher;
use App\Services\Seo\SeoOwnershipGuard;
use App\Support\PostPublishGrowthSync;
use Illuminate\Support\Facades\Schema;

class PageObserver
{
    public function __construct(
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly SitemapRegenerationDispatcher $sitemapDispatcher,
    ) {}

    public function saving(Page $page): void
    {
        if (SeoOwnershipGuard::skipAutofillOnGeneratedPages() && SeoOwnershipGuard::isGeneratedPage($page)) {
            return;
        }

        $this->contentSeoAutoFill->applyToPage($page);
    }

    public function saved(Page $page): void
    {
        if (! (SeoOwnershipGuard::skipPageSeoForGeneratedPages() && SeoOwnershipGuard::isGeneratedPage($page))) {
            $this->contentSeoAutoFill->syncPageGrowthArtifacts($page);
        }

        PostPublishGrowthSync::defer();
        $this->sitemapDispatcher->dispatch();
    }

    public function deleted(Page $page): void
    {
        $slugPath = $page->publicPath();

        if (Schema::hasTable('page_seo')) {
            PageSeo::query()->where('page_slug', $slugPath)->delete();
        }

        if (Schema::hasTable('page_elements')) {
            PageElement::query()->where('page_slug', $slugPath)->delete();
        }

        PostPublishGrowthSync::defer();
    }
}
