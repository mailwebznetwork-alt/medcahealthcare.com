<?php

namespace App\Services\Governance;

use App\Models\AdminRemovedMapping;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\ActivityLogService;

final class MasterDataAudit
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly AutomatedWriteAuditLogger $automatedAudit,
    ) {}

    public function serviceCreated(Service $service, string $source): void
    {
        $this->entityLog('service_created', 'services', $service->service_code, $service->id, $source, 'Service created.');
    }

    public function serviceUpdated(Service $service, string $source): void
    {
        $this->entityLog('service_updated', 'services', $service->service_code, $service->id, $source, 'Service updated.');
    }

    public function serviceDeleted(Service $service, string $source, ?string $reason = null): void
    {
        $this->entityLog('service_deleted', 'services', $service->service_code, $service->id, $source, 'Service deleted.', $reason);
    }

    public function serviceRecreationBlocked(string $serviceCode, string $source, string $reason): void
    {
        $this->recreationBlocked('service', 'services', $serviceCode, $source, $reason, 'service_recreation_blocked');
    }

    public function categoryCreated(ServiceCategory $category, string $source): void
    {
        $this->entityLog('category_created', 'service_categories', $category->code, $category->id, $source, 'Category created.');
    }

    public function categoryUpdated(ServiceCategory $category, string $source): void
    {
        $this->entityLog('category_updated', 'service_categories', $category->code, $category->id, $source, 'Category updated.');
    }

    public function categoryDeleted(ServiceCategory $category, string $source, ?string $reason = null): void
    {
        $this->entityLog('category_deleted', 'service_categories', $category->code, $category->id, $source, 'Category deleted.', $reason);
    }

    public function categoryRecreationBlocked(string $code, string $source, string $reason): void
    {
        $this->recreationBlocked('category', 'service_categories', $code, $source, $reason, 'category_recreation_blocked');
    }

    public function subServiceCreated(SubService $sub, string $source): void
    {
        $key = $this->subServiceKey($sub);
        $this->entityLog('sub_service_created', 'sub_services', $key, $sub->id, $source, 'Sub-service created.');
    }

    public function subServiceUpdated(SubService $sub, string $source): void
    {
        $key = $this->subServiceKey($sub);
        $this->entityLog('sub_service_updated', 'sub_services', $key, $sub->id, $source, 'Sub-service updated.');
    }

    public function subServiceDeleted(SubService $sub, string $source, ?string $reason = null): void
    {
        $key = $this->subServiceKey($sub);
        $this->entityLog('sub_service_deleted', 'sub_services', $key, $sub->id, $source, 'Sub-service deleted.', $reason);
    }

    public function subServiceRecreationBlocked(string $naturalKey, string $source, string $reason): void
    {
        $this->recreationBlocked('sub_service', 'sub_services', $naturalKey, $source, $reason, 'sub_service_recreation_blocked');
    }

    public function mappingRemovalBlocked(string $serviceCode, string $pincode, string $source, string $reason): void
    {
        $key = AdminRemovedMapping::servicePincodeKey($serviceCode, $pincode);
        $this->recreationBlocked('mapping', 'service_pincodes', $key, $source, $reason, 'mapping_reattach_blocked');
    }

    public function mappingRemoved(string $serviceCode, string $pincode, string $source, ?string $reason = null): void
    {
        $key = AdminRemovedMapping::servicePincodeKey($serviceCode, $pincode);
        $this->activityLog->log(
            'mapping_removed',
            'operations',
            "Service-pincode mapping removed [{$key}] source={$source} user=".(auth()->id() ?? 'system'),
        );

        $this->automatedAudit->log(
            process: $source,
            action: 'mapping_removed',
            table: 'service_pincodes',
            recordId: null,
            recordKey: $key,
            outcome: 'applied',
            reason: $reason,
        );
    }

    private function subServiceKey(SubService $sub): string
    {
        $sub->loadMissing('service');

        return ($sub->service?->service_code ?? 'unknown').'/'.$sub->sub_service_code;
    }

    private function entityLog(
        string $action,
        string $table,
        string $recordKey,
        ?int $recordId,
        string $source,
        string $description,
        ?string $reason = null,
    ): void {
        if ($source !== 'bulk') {
            $this->activityLog->log(
                $action,
                'operations',
                "{$description} [{$recordKey}] source={$source} user=".(auth()->id() ?? 'system'),
            );
        }

        $this->automatedAudit->log(
            process: $source,
            action: $action,
            table: $table,
            recordId: $recordId,
            recordKey: $recordKey,
            outcome: 'applied',
            reason: $reason,
        );
    }

    private function recreationBlocked(
        string $entity,
        string $table,
        string $recordKey,
        string $source,
        string $reason,
        string $action,
    ): void {
        $this->activityLog->log(
            $action,
            'operations',
            "Blocked {$entity} recreation [{$recordKey}] from {$source}: {$reason}",
        );

        $this->automatedAudit->blocked(
            process: $source,
            action: $action,
            table: $table,
            recordId: null,
            recordKey: $recordKey,
            reason: $reason,
        );
    }
}
