<?php

namespace App\Policies;

use App\Models\User;
use App\ModuleAccess;
use App\Support\RootAccount;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::USER_MANAGEMENT);
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->hasModuleAccess(ModuleAccess::USER_MANAGEMENT)) {
            return false;
        }

        if ($model->isRootSuperAdmin() && ! RootAccount::isRootUser($user)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasModuleAccess(ModuleAccess::USER_MANAGEMENT)) {
            return false;
        }

        if ($model->isRootSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Toggle active flag (separate from full profile update).
     */
    public function changeActiveState(User $user, User $model): bool
    {
        if (! $user->hasModuleAccess(ModuleAccess::USER_MANAGEMENT)) {
            return false;
        }

        if ($model->isRootSuperAdmin()) {
            return false;
        }

        return true;
    }
}
