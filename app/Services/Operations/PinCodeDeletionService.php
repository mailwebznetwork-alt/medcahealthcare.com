<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\PinCodeMasterDataAudit;
use Illuminate\Support\Facades\DB;

final class PinCodeDeletionService
{
    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly DownstreamArtifactPurger $purger,
        private readonly PinCodeMasterDataAudit $audit,
    ) {}

    public function delete(PinCode $pinCode, string $source = 'ui', ?string $reason = null): void
    {
        DB::transaction(function () use ($pinCode, $source, $reason): void {
            $this->deletionGuard->recordPinCodeDeletion($pinCode, $source, $reason);
            $pinCode->delete();
            $this->audit->deleted($pinCode, $source, $reason);
        });

        $this->purger->purgeAfterCatalogEntityChange();
    }
}
