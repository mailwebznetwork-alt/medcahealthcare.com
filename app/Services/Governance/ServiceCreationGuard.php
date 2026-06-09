<?php

namespace App\Services\Governance;

use App\Models\Service;

final class ServiceCreationGuard
{
    /** Admin/UI paths that may intentionally re-add a previously deleted service. */
    private const EXPLICIT_SOURCES = ['ui', 'import'];

    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly MasterDataAudit $audit,
        private readonly MasterDataProtection $protection,
    ) {}

    public function isExplicitSource(string $source): bool
    {
        return in_array($source, self::EXPLICIT_SOURCES, true);
    }

    public function canCreateService(string $serviceCode, string $source = 'system'): bool
    {
        $code = $this->normalizeCode($serviceCode);
        if ($code === null) {
            return false;
        }

        if (! $this->protection->allowsWrite($source)) {
            $this->audit->serviceRecreationBlocked($code, $source, 'Master data protection is enabled.');

            return false;
        }

        if (
            $this->deletionGuard->isServicePermanentlyDeleted($code)
            && ! $this->isExplicitSource($source)
        ) {
            $this->audit->serviceRecreationBlocked($code, $source, 'Service was permanently deleted by admin.');

            return false;
        }

        return true;
    }

    /**
     * Tombstones block automatic recreation only. Explicit admin import/UI may re-add.
     */
    public function resolveForExplicitRecreate(string $serviceCode, string $source): ?Service
    {
        if (! $this->isExplicitSource($source)) {
            return null;
        }

        $code = $this->normalizeCode($serviceCode);
        if ($code === null || ! $this->canCreateService($code, $source)) {
            return null;
        }

        if ($this->deletionGuard->isServicePermanentlyDeleted($code)) {
            $this->deletionGuard->clearServiceTombstone($code);
        }

        return null;
    }

    public function normalizeCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $code = trim($value);

        return $code !== '' ? $code : null;
    }
}
