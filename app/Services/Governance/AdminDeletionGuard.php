<?php

namespace App\Services\Governance;

use App\Models\AdminDeletionTombstone;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Governance\SubServiceCreationGuard;
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

    public function recordServiceDeletion(Service $service, string $source = 'ui', ?string $reason = null): void
    {
        AdminDeletionTombstone::record(
            entityType: 'service',
            naturalKey: $service->service_code,
            userId: auth()->id(),
            source: $source,
            reason: $reason,
        );
    }

    public function isCategoryPermanentlyDeleted(string $code): bool
    {
        return AdminDeletionTombstone::exists('category', $code);
    }

    public function recordCategoryDeletion(ServiceCategory $category, string $source = 'ui', ?string $reason = null): void
    {
        AdminDeletionTombstone::record(
            entityType: 'category',
            naturalKey: $category->code,
            userId: auth()->id(),
            source: $source,
            reason: $reason,
        );
    }

    public function isSubServicePermanentlyDeleted(string $naturalKey): bool
    {
        return AdminDeletionTombstone::exists('sub_service', $naturalKey);
    }

    public function recordSubServiceDeletion(SubService $sub, string $source = 'ui', ?string $reason = null): void
    {
        AdminDeletionTombstone::record(
            entityType: 'sub_service',
            naturalKey: SubServiceCreationGuard::naturalKeyFromModel($sub),
            userId: auth()->id(),
            source: $source,
            reason: $reason,
        );
    }

    public function isPinCodePermanentlyDeleted(string $pincode): bool
    {
        return AdminDeletionTombstone::exists('pin_code', $pincode);
    }

    public function canRecreatePinCode(string $pincode, string $process): bool
    {
        if (! $this->isPinCodePermanentlyDeleted($pincode)) {
            return true;
        }

        $this->audit->blocked(
            process: $process,
            action: 'recreate_pin_code',
            table: 'pin_codes',
            recordId: null,
            recordKey: $pincode,
            reason: 'Pincode permanently deleted by admin; automatic recreation blocked.',
        );

        return false;
    }

    public function recordPinCodeDeletion(PinCode $pinCode, string $source = 'ui', ?string $reason = null): void
    {
        AdminDeletionTombstone::record(
            entityType: 'pin_code',
            naturalKey: $pinCode->pincode,
            userId: auth()->id(),
            source: $source,
            reason: $reason,
        );
    }

    public function clearPinCodeTombstone(string $pincode): void
    {
        AdminDeletionTombstone::forget('pin_code', $pincode);
    }

    public function clearServiceTombstone(string $serviceCode): void
    {
        AdminDeletionTombstone::forget('service', $serviceCode);
    }

    public function clearCategoryTombstone(string $code): void
    {
        AdminDeletionTombstone::forget('category', $code);
    }

    public function clearSubServiceTombstone(string $naturalKey): void
    {
        AdminDeletionTombstone::forget('sub_service', $naturalKey);
    }
}
