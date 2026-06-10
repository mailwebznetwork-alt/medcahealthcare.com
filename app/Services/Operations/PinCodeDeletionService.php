<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\PinCodeMasterDataAudit;
use App\Services\Operations\ServiceLocationPageProvisioner;
use Illuminate\Support\Collection;
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
    }

    /**
     * @param  Collection<int, PinCode>|iterable<int, PinCode>  $pinCodes
     */
    public function deleteMany(iterable $pinCodes, string $source = 'bulk', ?string $reason = null): int
    {
        $collection = $pinCodes instanceof Collection ? $pinCodes : collect($pinCodes);

        if ($collection->isEmpty()) {
            return 0;
        }

        $pinIds = $collection->pluck('id')->all();
        $deleted = 0;

        PinCode::withoutEvents(function () use ($collection, $pinIds, $source, $reason, &$deleted): void {
            DB::transaction(function () use ($collection, $pinIds, $source, $reason, &$deleted): void {
                app(ServiceLocationPageProvisioner::class)->bulkDeleteLocationArtifactsForPinIds($pinIds);

                foreach ($collection as $pinCode) {
                    $this->deletionGuard->recordPinCodeDeletion($pinCode, $source, $reason);
                    $pinCode->delete();
                    $this->audit->deleted($pinCode, $source, $reason);
                    $deleted++;
                }
            });
        });

        if ($deleted > 0) {
            $this->purger->purgeAfterBulkPinCodeDeletion();
        }

        return $deleted;
    }
}
