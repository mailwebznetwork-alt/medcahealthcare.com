<?php

namespace App\Observers;

use App\Models\ServiceLocationPage;
use App\Services\Growth\SitemapRegenerationDispatcher;
use App\Services\Operations\InternalLinkRefreshDispatcher;

class ServiceLocationPageObserver
{
    public function __construct(
        private readonly InternalLinkRefreshDispatcher $linkRefreshDispatcher,
        private readonly SitemapRegenerationDispatcher $sitemapDispatcher,
    ) {}

    public function saved(ServiceLocationPage $mapping): void
    {
        $this->linkRefreshDispatcher->dispatchForService($mapping->service_id);
        $this->sitemapDispatcher->dispatch();
    }

    public function deleted(ServiceLocationPage $mapping): void
    {
        $this->linkRefreshDispatcher->dispatchForService($mapping->service_id);
        $this->sitemapDispatcher->dispatch();
    }
}
