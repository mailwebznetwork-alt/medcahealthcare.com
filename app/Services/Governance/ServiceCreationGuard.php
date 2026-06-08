<?php

namespace App\Services\Governance;

final class ServiceCreationGuard
{
    public function __construct(
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly MasterDataAudit $audit,
        private readonly MasterDataProtection $protection,
    ) {}

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

        if ($this->deletionGuard->isServicePermanentlyDeleted($code)) {
            $this->audit->serviceRecreationBlocked($code, $source, 'Service was permanently deleted by admin.');

            return false;
        }

        return true;
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
