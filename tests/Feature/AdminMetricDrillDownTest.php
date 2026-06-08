<?php

use App\Models\User;
use App\ModuleAccess;
use App\Support\AdminMetricLinks;

function metricDrillDownGrants(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

it('links security overview metrics to related sections', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => metricDrillDownGrants(),
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('modules.security'))
        ->assertOk()
        ->assertSee('href="'.AdminMetricLinks::security('security-failed-logins').'"', false)
        ->assertSee('href="'.AdminMetricLinks::security('security-audit').'"', false)
        ->assertSee('href="'.AdminMetricLinks::security('security-activity').'"', false);
});

it('links job portal overview metrics to vacancies and applications', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => metricDrillDownGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($user)
        ->get(route('operations.job-portal.overview'))
        ->assertOk()
        ->assertSee('href="'.AdminMetricLinks::jobPortalVacancies().'"', false)
        ->assertSee('href="'.AdminMetricLinks::jobPortalVacancies('published').'"', false)
        ->assertSee('href="'.AdminMetricLinks::jobPortalApplications().'"', false);
});

it('opens marketing intelligence tab from query string', function () {
    $user = User::factory()->create([
        'role' => 'manager',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('marketing.intelligence', ['tab' => 'calls']))
        ->assertSuccessful()
        ->assertSee(__('Calls — Today'), false);
});

it('links lead intent metrics to marketing intelligence tabs', function () {
    $user = User::factory()->create([
        'role' => 'manager',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('marketing.dashboard', ['tab' => 'lead-intent']))
        ->assertSuccessful()
        ->assertSee('href="'.AdminMetricLinks::marketingIntelligence('calls').'"', false)
        ->assertSee('href="'.AdminMetricLinks::marketingIntelligence('whatsapp').'"', false)
        ->assertSee('href="'.AdminMetricLinks::marketingIntelligence('conversions').'"', false);
});
