<?php

namespace App\Services\Operations;

use App\Models\SubService;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\MasterDataAudit;
use Illuminate\Support\Facades\DB;

final class SubServiceDeletionService
{
    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly MasterDataAudit $audit,
    ) {}

    public function delete(SubService $subService, string $source = 'ui', ?string $reason = null): void
    {
        DB::transaction(function () use ($subService, $source, $reason): void {
            $this->deletionGuard->recordSubServiceDeletion($subService, $source, $reason);
            $this->audit->subServiceDeleted($subService, $source, $reason);
            $subService->delete();
        });
    }
}
