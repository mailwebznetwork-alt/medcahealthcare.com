<?php

namespace App\Policies;

use App\Models\RevenueEvent;
use App\Models\User;
use App\ModuleAccess;

class RevenueEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function update(User $user, RevenueEvent $revenueEvent): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function delete(User $user, RevenueEvent $revenueEvent): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }
}
