<?php

namespace App\Services\Governance;

use App\Models\SubService;

final class SubServiceCreationGuard
{
    /** Admin/UI paths that may intentionally re-add a previously deleted sub-service. */
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

    public function canCreateSubService(string $parentServiceCode, string $subServiceCode, string $source = 'system'): bool
    {
        $parent = trim($parentServiceCode);
        $sub = trim($subServiceCode);
        if ($parent === '' || $sub === '') {
            return false;
        }

        $naturalKey = self::naturalKey($parent, $sub);

        if (! $this->protection->allowsWrite($source)) {
            $this->audit->subServiceRecreationBlocked($naturalKey, $source, 'Master data protection is enabled.');

            return false;
        }

        if (
            $this->deletionGuard->isSubServicePermanentlyDeleted($naturalKey)
            && ! $this->isExplicitSource($source)
        ) {
            $this->audit->subServiceRecreationBlocked($naturalKey, $source, 'Sub-service was permanently deleted by admin.');

            return false;
        }

        return true;
    }

    /**
     * Tombstones block automatic recreation only. Explicit admin import/UI may re-add.
     */
    public function resolveForExplicitRecreate(string $parentServiceCode, string $subServiceCode, string $source): ?SubService
    {
        if (! $this->isExplicitSource($source)) {
            return null;
        }

        $naturalKey = self::naturalKey($parentServiceCode, $subServiceCode);
        if (! $this->canCreateSubService($parentServiceCode, $subServiceCode, $source)) {
            return null;
        }

        if ($this->deletionGuard->isSubServicePermanentlyDeleted($naturalKey)) {
            $this->deletionGuard->clearSubServiceTombstone($naturalKey);
        }

        return null;
    }

    public static function naturalKey(string $parentServiceCode, string $subServiceCode): string
    {
        return trim($parentServiceCode).'/'.trim($subServiceCode);
    }

    public static function naturalKeyFromModel(SubService $sub): string
    {
        $sub->loadMissing('service');

        return self::naturalKey(
            (string) ($sub->service?->service_code ?? 'unknown'),
            (string) $sub->sub_service_code,
        );
    }
}
