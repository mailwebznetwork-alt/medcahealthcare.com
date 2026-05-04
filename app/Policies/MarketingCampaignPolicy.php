<?php

namespace App\Policies;

use App\Models\MarketingCampaign;
use App\Models\User;
use App\ModuleAccess;

class MarketingCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }

    public function view(User $user, MarketingCampaign $marketingCampaign): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }

    public function update(User $user, MarketingCampaign $marketingCampaign): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }

    public function delete(User $user, MarketingCampaign $marketingCampaign): bool
    {
        return $user->hasModuleAccess(ModuleAccess::MARKETING);
    }
}
