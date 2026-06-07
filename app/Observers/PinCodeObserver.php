<?php

namespace App\Observers;

use App\Models\PinCode;
use App\Models\Service;
use App\Services\Operations\InternalLinkRefreshDispatcher;

class PinCodeObserver
{
    public function __construct(
        private readonly InternalLinkRefreshDispatcher $linkRefreshDispatcher,
    ) {}

    public function saved(PinCode $pinCode): void
    {
        $this->refreshMappedServices($pinCode);
    }

    public function deleted(PinCode $pinCode): void
    {
        $this->refreshMappedServices($pinCode);
    }

    private function refreshMappedServices(PinCode $pinCode): void
    {
        $pinCode->services()->pluck('services.id')->each(function (int $serviceId): void {
            $this->linkRefreshDispatcher->dispatchForService($serviceId);
        });
    }
}
