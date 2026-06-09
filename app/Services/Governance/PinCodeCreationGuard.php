<?php

namespace App\Services\Governance;

use App\Models\PinCode;

final class PinCodeCreationGuard
{
    /** Admin/UI paths that may intentionally re-add a previously deleted pincode. */
    private const EXPLICIT_SOURCES = ['ui', 'import'];

    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly PinCodeMasterDataAudit $audit,
        private readonly MasterDataProtection $protection,
    ) {}

    public function isExplicitSource(string $source): bool
    {
        return in_array($source, self::EXPLICIT_SOURCES, true);
    }

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

        if (
            $this->deletionGuard->isPinCodePermanentlyDeleted($normalized)
            && ! $this->isExplicitSource($source)
        ) {
            $this->audit->recreationBlocked($normalized, $source, 'Pincode was permanently deleted by admin.');

            return false;
        }

        return true;
    }

    /**
     * Tombstones block automatic recreation only. Explicit admin import/UI may restore.
     */
    public function resolveForExplicitRecreate(string $pincode, string $source): ?PinCode
    {
        if (! $this->isExplicitSource($source)) {
            return null;
        }

        $normalized = $this->normalizePincode($pincode);
        if ($normalized === null || ! $this->canCreatePincode($normalized, $source)) {
            return null;
        }

        if ($this->deletionGuard->isPinCodePermanentlyDeleted($normalized)) {
            $this->deletionGuard->clearPinCodeTombstone($normalized);
        }

        $trashed = PinCode::withTrashed()->where('pincode', $normalized)->first();
        if ($trashed?->trashed()) {
            $trashed->restore();

            return $trashed->fresh();
        }

        return null;
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
