<?php

namespace App\Services\Governance;

use App\Models\AdminDeletionTombstone;
use App\Models\Service;
use App\Services\Governance\AutomatedWriteAuditLogger;

class AdminDeletionGuard
{
    public function __construct(
        private readonly AutomatedWriteAuditLogger $audit,
    ) {}

    public function isServicePermanentlyDeleted(string $serviceCode): bool
    {
        return AdminDeletionTombstone::exists('service', $serviceCode);
    }

    public function canSeedService(string $serviceCode, string $process): bool
    {
        if (! $this->isServicePermanentlyDeleted($serviceCode)) {
            return true;
        }

        $this->audit->blocked(
            process: $process,
            action: 'reseed_service',
            table: 'services',
            recordId: null,
            recordKey: $serviceCode,
            reason: 'Service permanently deleted by admin; seeders cannot recreate.',
        );

        return false;
    }

    public function canProvisionService(?Service $service, string $serviceCode, string $process): bool
    {
        if ($this->isServicePermanentlyDeleted($serviceCode)) {
            $this->audit->blocked(
                process: $process,
                action: 'provision_service',
                table: 'services',
                recordId: $service?->id,
                recordKey: $serviceCode,
                reason: 'Service permanently deleted by admin; provisioners cannot recreate.',
            );

            return false;
        }

        return true;
    }

    public function recordServiceDeletion(Service $service): void
    {
        AdminDeletionTombstone::record('service', $service->service_code, auth()->id());
    }
}
