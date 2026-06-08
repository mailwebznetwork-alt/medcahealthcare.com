<?php

namespace App\Services\Governance;

use App\Concerns\HasAdminLifecycle;
use App\Enums\AdminLifecycleState;
use App\Models\Block;
use Illuminate\Database\Eloquent\Model;

/**
 * Enforces: database is authoritative; automated processes must not recreate admin-deleted records.
 */
class AdminAuthorityGuard
{
    public function __construct(
        private readonly AutomatedWriteAuditLogger $audit,
    ) {}

    public function canRestoreBlock(Block $block, string $process): bool
    {
        if (! config('governance.enforce_admin_authority', true)) {
            return true;
        }

        if ($block->isDeletedByAdmin()) {
            $this->audit->blocked(
                process: $process,
                action: 'restore_block',
                table: 'blocks',
                recordId: $block->id,
                recordKey: $block->block_slug,
                reason: 'Block marked deleted_by_admin; auto-heal cannot restore.',
            );

            return false;
        }

        return true;
    }

    public function canRecreateBlockSlug(string $slug, string $process): bool
    {
        if (! config('governance.enforce_admin_authority', true)) {
            return true;
        }

        $deleted = Block::withTrashed()
            ->where('block_slug', $slug)
            ->where('lifecycle_state', AdminLifecycleState::DeletedByAdmin->value)
            ->exists();

        if ($deleted) {
            $this->audit->blocked(
                process: $process,
                action: 'recreate_block',
                table: 'blocks',
                recordId: null,
                recordKey: $slug,
                reason: 'Block slug permanently excluded after admin deletion.',
            );

            return false;
        }

        return true;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function canProvision(string $modelClass, string $naturalKey, string $column, string $process): bool
    {
        if (! config('governance.enforce_admin_authority', true)) {
            return true;
        }

        if (! method_exists($modelClass, 'query')) {
            return true;
        }

        $existing = $modelClass::withTrashed()
            ->where($column, $naturalKey)
            ->where('lifecycle_state', AdminLifecycleState::DeletedByAdmin->value)
            ->first();

        if ($existing === null) {
            return true;
        }

        $this->audit->blocked(
            process: $process,
            action: 'provision_record',
            table: $existing->getTable(),
            recordId: $existing->getKey(),
            recordKey: $naturalKey,
            reason: 'Record marked deleted_by_admin; provisioner cannot recreate.',
        );

        return false;
    }

    /**
     * @param  Model&HasAdminLifecycle  $model
     */
    public function markDeletedByAdmin(Model $model): void
    {
        if (! in_array(HasAdminLifecycle::class, class_uses_recursive($model), true)) {
            return;
        }

        $model->markLifecycle(AdminLifecycleState::DeletedByAdmin)->saveQuietly();
    }

    public function markSystemManaged(Model $model): void
    {
        if (! in_array(HasAdminLifecycle::class, class_uses_recursive($model), true)) {
            return;
        }

        if ($model->lifecycleState() === AdminLifecycleState::DeletedByAdmin) {
            return;
        }

        $model->markLifecycle(AdminLifecycleState::SystemManaged)->saveQuietly();
    }
}
