<?php

namespace App\Http\Controllers;

use App\Enums\VacancyWorkflowStatus;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            'settings' => $user->hasModuleAccess(ModuleAccess::SETTINGS),
        ];

        $metrics = $this->buildMetrics($dashboardWidgets);

        return view('dashboard', [
            'dashboardWidgets' => $dashboardWidgets,
            'metrics' => $metrics,
        ]);
    }

    /**
     * @param  array<string, bool>  $dashboardWidgets
     * @return array<string, mixed>
     */
    private function buildMetrics(array $dashboardWidgets): array
    {
        $metrics = [
            'users_total' => null,
            'users_active' => null,
            'users_inactive' => null,
            'users_verified' => null,
            'recent_users' => collect(),
            'vacancies_total' => null,
            'vacancies_published' => null,
            'applications_recent' => null,
        ];

        if ($dashboardWidgets['user_management'] ?? false) {
            $metrics['users_total'] = User::query()->count();
            $metrics['users_active'] = User::query()->where('is_active', true)->count();
            $metrics['users_inactive'] = User::query()->where('is_active', false)->count();
            $metrics['users_verified'] = User::query()->whereNotNull('email_verified_at')->count();
            $metrics['recent_users'] = User::query()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'email', 'role_label', 'is_active', 'profile_image_path']);
        }

        $hasVacancies = Schema::hasTable('vacancies');
        $hasApplications = Schema::hasTable('applications');

        if (($dashboardWidgets['operations'] ?? false) && $hasVacancies) {
            $metrics['vacancies_total'] = Vacancy::query()->count();
            $metrics['vacancies_published'] = Vacancy::query()
                ->where('workflow_status', VacancyWorkflowStatus::Published)
                ->count();
        }

        if (($dashboardWidgets['operations'] ?? false) && $hasApplications) {
            $metrics['applications_recent'] = Application::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        }

        return $metrics;
    }
}
