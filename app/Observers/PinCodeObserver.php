<?php

namespace App\Observers;

use App\Models\PinCode;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Operations\InternalLinkRefreshDispatcher;
use App\Services\Operations\ServiceLocationPageProvisioner;

class PinCodeObserver
{
    public function __construct(
        private readonly InternalLinkRefreshDispatcher $linkRefreshDispatcher,
        private readonly ServiceLocationPageProvisioner $locationPageProvisioner,
        private readonly AdminDeletionGuard $deletionGuard,
    ) {}

    public function saved(PinCode $pinCode): void
    {
        if ($pinCode->trashed()) {
            return;
        }

        if ($this->deletionGuard->isPinCodePermanentlyDeleted($pinCode->pincode)) {
            return;
        }

        $this->locationPageProvisioner->syncAllForPincode($pinCode);
        $this->refreshMappedServices($pinCode);
    }

    public function deleting(PinCode $pinCode): void
    {
        $this->locationPageProvisioner->deleteAllForPincode($pinCode);
    }

    private function refreshMappedServices(PinCode $pinCode): void
    {
        $pinCode->services()->pluck('services.id')->each(function (int $serviceId): void {
            $this->linkRefreshDispatcher->dispatchForService($serviceId);
        });
    }
}
