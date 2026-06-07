<?php

namespace App\Services\Operations;

use App\Jobs\Operations\RefreshPeerServiceInternalLinksJob;
use App\Jobs\Operations\RefreshServiceInternalLinksJob;
use App\Models\Service;

class InternalLinkRefreshDispatcher
{
    public function dispatchForService(Service|int $service, bool $includePeers = true): void
    {
        $serviceId = $service instanceof Service ? $service->id : $service;

        RefreshServiceInternalLinksJob::dispatch($serviceId);

        if ($includePeers) {
            RefreshPeerServiceInternalLinksJob::dispatch($serviceId);
        }
    }
}
