<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;
use App\ModuleAccess;
use App\Support\ArchitectSaveBypass;

class BlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function view(User $user, Block $block): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function update(User $user, Block $block): bool
    {
        return $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT);
    }

    public function delete(User $user, Block $block): bool
    {
        if (! $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT)) {
            return false;
        }

        if ($block->is_managed) {
            return ArchitectSaveBypass::eligible($user);
        }

        return true;
    }

    public function forceDelete(User $user, Block $block): bool
    {
        return false;
    }
}
