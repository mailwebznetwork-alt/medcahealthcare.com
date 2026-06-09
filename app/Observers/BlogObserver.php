<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Services\Growth\ContentSeoAutoFillService;
use App\Support\PostPublishGrowthSync;
use Illuminate\Support\Facades\Schema;

class BlogObserver
{
    public function __construct(
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
    ) {}

    public function saving(Blog $blog): void
    {
        $this->contentSeoAutoFill->applyToBlog($blog);
    }

    public function saved(Blog $blog): void
    {
        $this->contentSeoAutoFill->syncBlogGrowthArtifacts($blog);
        PostPublishGrowthSync::defer();
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

        PostPublishGrowthSync::defer();
    }
}
