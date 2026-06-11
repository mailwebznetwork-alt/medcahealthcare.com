<?php

namespace App\Policies;

use App\Models\Admission;
use App\Models\User;
use App\ModuleAccess;

class AdmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function view(User $user, Admission $admission): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function update(User $user, Admission $admission): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function delete(User $user, Admission $admission): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }
}
