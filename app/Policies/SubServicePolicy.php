<?php

namespace App\Policies;

use App\Models\SubService;
use App\Models\User;
use App\ModuleAccess;

class SubServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function view(User $user, SubService $subService): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SubService $subService): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, SubService $subService): bool
    {
        return $this->viewAny($user);
    }
}
