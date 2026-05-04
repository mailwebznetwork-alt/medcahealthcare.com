<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\ModuleAccess;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }
}
