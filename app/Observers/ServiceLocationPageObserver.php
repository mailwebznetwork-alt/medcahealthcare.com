<?php

namespace App\Observers;

use App\Models\ServiceLocationPage;
use App\Services\Operations\InternalLinkRefreshDispatcher;

class ServiceLocationPageObserver
{
    public function __construct(
        private readonly InternalLinkRefreshDispatcher $linkRefreshDispatcher,
    ) {}

    public function saved(ServiceLocationPage $mapping): void
    {
        $this->linkRefreshDispatcher->dispatchForService($mapping->service_id);
    }

    public function deleted(ServiceLocationPage $mapping): void
    {
        $this->linkRefreshDispatcher->dispatchForService($mapping->service_id);
    }
}
