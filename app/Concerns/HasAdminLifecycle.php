<?php

namespace App\Concerns;

use App\Enums\AdminLifecycleState;

trait HasAdminLifecycle
{
    public function lifecycleState(): AdminLifecycleState
    {
        $raw = $this->getAttribute('lifecycle_state');

        return AdminLifecycleState::tryFrom((string) $raw) ?? AdminLifecycleState::Active;
    }

    public function markLifecycle(AdminLifecycleState $state): static
    {
        $this->forceFill(['lifecycle_state' => $state->value]);

        return $this;
    }

    public function isDeletedByAdmin(): bool
    {
        return $this->lifecycleState() === AdminLifecycleState::DeletedByAdmin;
    }

    public function allowsAutoHeal(): bool
    {
        if (! config('governance.enforce_admin_authority', true)) {
            return true;
        }

        return $this->lifecycleState()->allowsAutoHeal();
    }

    public function allowsRecreation(): bool
    {
        if (! config('governance.enforce_admin_authority', true)) {
            return true;
        }

        return $this->lifecycleState()->allowsRecreation();
    }
}
