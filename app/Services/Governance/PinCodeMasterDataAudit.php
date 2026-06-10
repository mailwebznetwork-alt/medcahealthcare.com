<?php

namespace App\Services\Governance;

use App\Models\PinCode;
use App\Services\ActivityLogService;

final class PinCodeMasterDataAudit
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly AutomatedWriteAuditLogger $automatedAudit,
    ) {}

    public function created(PinCode $pinCode, string $source): void
    {
        $this->log('pincode_created', $pinCode, $source, 'Pincode created.');
    }

    public function updated(PinCode $pinCode, string $source): void
    {
        $this->log('pincode_updated', $pinCode, $source, 'Pincode updated.');
    }

    public function deleted(PinCode $pinCode, string $source, ?string $reason = null): void
    {
        if ($source !== 'bulk') {
            $description = 'Pincode deleted'.($reason ? ": {$reason}" : '.');
            $this->log('pincode_deleted', $pinCode, $source, $description);
        }

        $this->automatedAudit->log(
            process: $source,
            action: 'pincode_deleted',
            table: 'pin_codes',
            recordId: $pinCode->id,
            recordKey: $pinCode->pincode,
            outcome: 'applied',
            reason: $reason,
        );
    }

    public function restored(PinCode $pinCode, string $source): void
    {
        $this->log('pincode_restored', $pinCode, $source, 'Pincode restored.');
    }

    public function recreationBlocked(string $pincode, string $source, string $reason): void
    {
        $this->activityLog->log(
            'pincode_recreation_blocked',
            'operations',
            "Blocked recreation of pincode {$pincode} from {$source}: {$reason}",
        );

        $this->automatedAudit->blocked(
            process: $source,
            action: 'pincode_recreation_blocked',
            table: 'pin_codes',
            recordId: null,
            recordKey: $pincode,
            reason: $reason,
        );
    }

    private function log(string $action, PinCode $pinCode, string $source, string $description): void
    {
        $this->activityLog->log(
            $action,
            'operations',
            "{$description} [{$pinCode->pincode}] source={$source} user=".(auth()->id() ?? 'system'),
        );
    }
}
