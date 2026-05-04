<?php

namespace App\Policies;

use App\Models\MarketingSetting;
use App\Models\User;
use App\ModuleAccess;

class MarketingSettingPolicy
{
    public function view(User $user, MarketingSetting $marketingSetting): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }

    public function update(User $user, MarketingSetting $marketingSetting): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }
}
