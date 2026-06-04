<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;
use App\ModuleAccess;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS);
    }

    public function view(User $user, ServiceCategory $serviceCategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ServiceCategory $serviceCategory): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, ServiceCategory $serviceCategory): bool
    {
        return $this->viewAny($user);
    }
}
