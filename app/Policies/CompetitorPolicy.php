<?php

namespace App\Policies;

use App\Models\Competitor;
use App\Models\User;
use App\ModuleAccess;

class CompetitorPolicy
{
    /** @var list<string> */
    private const MUTATING_ROLES = ['editor', 'manager', 'admin', 'super_admin'];

    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::GROWTH_CENTER);
    }

    public function view(User $user, Competitor $competitor): bool
    {
        return $user->hasModuleAccess(ModuleAccess::GROWTH_CENTER);
    }

    public function create(User $user): bool
    {
        return $this->canMutate($user);
    }

    public function update(User $user, Competitor $competitor): bool
    {
        return $this->canMutate($user);
    }

    public function delete(User $user, Competitor $competitor): bool
    {
        return $this->canMutate($user);
    }

    private function canMutate(User $user): bool
    {
        if (! $user->hasModuleAccess(ModuleAccess::GROWTH_CENTER)) {
            return false;
        }

        if ($user->isRootSuperAdmin()) {
            return true;
        }

        $role = trim((string) ($user->role ?? ''));

        return in_array($role, self::MUTATING_ROLES, true);
    }
}
