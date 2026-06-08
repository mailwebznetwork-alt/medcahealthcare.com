<?php

namespace App\Services\Governance;

use App\Models\PinCode;

final class PinCodeCreationGuard
{
    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly PinCodeMasterDataAudit $audit,
        private readonly MasterDataProtection $protection,
    ) {}

    public function canCreatePincode(string $pincode, string $source = 'system'): bool
    {
        $normalized = $this->normalizePincode($pincode);
        if ($normalized === null) {
            return false;
        }

        if (! $this->protection->allowsWrite($source)) {
            $this->audit->recreationBlocked($normalized, $source, 'Master data protection is enabled.');

            return false;
        }

        if ($this->deletionGuard->isPinCodePermanentlyDeleted($normalized)) {
            $this->audit->recreationBlocked($normalized, $source, 'Pincode was permanently deleted by admin.');

            return false;
        }

        return true;
    }

    /**
     * @return list<int>
     */
    public function filterEligiblePinIdsForSync(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return PinCode::query()
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function normalizePincode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $pin = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($pin) < 6 || strlen($pin) > 12) {
            return null;
        }

        return $pin;
    }
}
