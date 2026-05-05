<?php

namespace App\Http\Controllers;

use App\ModuleAccess;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.surface', [
            'title' => __('Settings'),
            'moduleKey' => ModuleAccess::SETTINGS,
            'securityMetrics' => null,
            'recentSecurityEvents' => [],
            'failedLoginByIp' => [],
        ]);
    }
}
