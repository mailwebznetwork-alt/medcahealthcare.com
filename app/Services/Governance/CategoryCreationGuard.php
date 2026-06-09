<?php

namespace App\Services\Governance;

use App\Models\ServiceCategory;
use Illuminate\Support\Str;

final class CategoryCreationGuard
{
    /** Admin/UI paths that may intentionally re-add a previously deleted category. */
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

        if (
            $this->deletionGuard->isCategoryPermanentlyDeleted($normalized)
            && ! $this->isExplicitSource($source)
        ) {
            $this->audit->categoryRecreationBlocked($normalized, $source, 'Category was permanently deleted by admin.');

            return false;
        }

        return true;
    }

    /**
     * Tombstones block automatic recreation only. Explicit admin import/UI may restore.
     */
    public function resolveForExplicitRecreate(string $code, string $source, ?string $slug = null): ?ServiceCategory
    {
        if (! $this->isExplicitSource($source)) {
            return null;
        }

        $normalized = $this->normalizeCode($code);
        if ($normalized === null || ! $this->canCreateCategory($normalized, $source)) {
            return null;
        }

        if ($this->deletionGuard->isCategoryPermanentlyDeleted($normalized)) {
            $this->deletionGuard->clearCategoryTombstone($normalized);
        }

        $trashed = ServiceCategory::withTrashed()->where('code', $normalized)->first();
        if ($trashed?->trashed()) {
            $this->deletionGuard->clearCategoryTombstone($trashed->code);
            $trashed->restore();

            return $trashed->fresh();
        }

        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            return null;
        }

        $trashedBySlug = ServiceCategory::withTrashed()->where('slug', $slug)->first();
        if ($trashedBySlug?->trashed()) {
            $this->deletionGuard->clearCategoryTombstone($trashedBySlug->code);

            if ($this->deletionGuard->isCategoryPermanentlyDeleted($normalized)) {
                $this->deletionGuard->clearCategoryTombstone($normalized);
            }

            $trashedBySlug->restore();

            return $trashedBySlug->fresh();
        }

        return null;
    }

    public function normalizeCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $code = ServiceCategory::normalizeCode($value);

        return $code !== '' ? $code : null;
    }

    private function normalizeSlug(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $slug = Str::slug(trim($value));

        return $slug !== '' ? $slug : null;
    }
}
