<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $dashboardWidgets = [
            'dashboard' => $user->hasModuleAccess(ModuleAccess::DASHBOARD),
            'site_architect' => $user->hasModuleAccess(ModuleAccess::SITE_ARCHITECT),
            'operations' => $user->hasModuleAccess(ModuleAccess::OPERATIONS),
            'marketing' => $user->hasModuleAccess(ModuleAccess::MARKETING),
            'growth_center' => $user->hasModuleAccess(ModuleAccess::GROWTH_CENTER),
            'user_management' => $user->hasModuleAccess(ModuleAccess::USER_MANAGEMENT),
            'security' => $user->hasModuleAccess(ModuleAccess::SECURITY),
        ];

        return view('dashboard', [
            'dashboardWidgets' => $dashboardWidgets,
        ]);
    }
}
