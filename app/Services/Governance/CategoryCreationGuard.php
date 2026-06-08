<?php

namespace App\Services\Governance;

use App\Models\ServiceCategory;

final class CategoryCreationGuard
{
    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly MasterDataAudit $audit,
        private readonly MasterDataProtection $protection,
    ) {}

    public function canCreateCategory(string $code, string $source = 'system'): bool
    {
        $normalized = $this->normalizeCode($code);
        if ($normalized === null) {
            return false;
        }

        if (! $this->protection->allowsWrite($source)) {
            $this->audit->categoryRecreationBlocked($normalized, $source, 'Master data protection is enabled.');

            return false;
        }

        if ($this->deletionGuard->isCategoryPermanentlyDeleted($normalized)) {
            $this->audit->categoryRecreationBlocked($normalized, $source, 'Category was permanently deleted by admin.');

            return false;
        }

        return true;
    }

    public function normalizeCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $code = ServiceCategory::normalizeCode($value);

        return $code !== '' ? $code : null;
    }
}
