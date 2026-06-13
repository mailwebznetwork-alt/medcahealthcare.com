<?php

namespace App\Observers;

use App\Models\ServiceLocationPage;
use App\Services\Growth\SitemapRegenerationDispatcher;
use App\Services\Import\ImportSideEffectsGate;
use App\Services\Operations\InternalLinkRefreshDispatcher;
use App\Services\Public\CatalogPublicCache;

class ServiceLocationPageObserver
{
    public function __construct(
        private readonly InternalLinkRefreshDispatcher $linkRefreshDispatcher,
        private readonly SitemapRegenerationDispatcher $sitemapDispatcher,
        private readonly CatalogPublicCache $publicCache,
    ) {}

    public function saved(ServiceLocationPage $mapping): void
    {
        if (app(ImportSideEffectsGate::class)->suppressed()) {
            return;
        }

        $this->linkRefreshDispatcher->dispatchForService($mapping->service_id);
        $this->publicCache->forgetForLocationMapping($mapping);
        $this->sitemapDispatcher->dispatch();
    }

    public function deleted(ServiceLocationPage $mapping): void
    {
        if (app(ImportSideEffectsGate::class)->suppressed()) {
            return;
        }

        $this->linkRefreshDispatcher->dispatchForService($mapping->service_id);
        $this->publicCache->forgetForLocationMapping($mapping);
        $this->sitemapDispatcher->dispatch();
    }
}
