<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use App\ModuleAccess;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function view(User $user, Media $media): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function update(User $user, Media $media): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }
}
