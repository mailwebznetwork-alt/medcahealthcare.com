<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;
use App\ModuleAccess;

class BlogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function view(User $user, Blog $blog): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function update(User $user, Blog $blog): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }
}
