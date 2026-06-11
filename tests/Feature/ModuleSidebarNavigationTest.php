<?php

use App\Models\User;
use App\ModuleAccess;
use App\Support\AdminNavigation;
use App\Support\ModuleSidebarNavigation;
use Database\Seeders\MedcaLaunchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MedcaLaunchSeeder::class);
});

it('defines nested navigation for all primary modules except dashboard', function () {
    expect(ModuleSidebarNavigation::nestedModuleKeys())->toContain(
        ModuleAccess::SITE_ARCHITECT,
        ModuleAccess::OPERATIONS,
        ModuleAccess::MARKETING,
        ModuleAccess::GROWTH_CENTER,
        ModuleAccess::USER_MANAGEMENT,
        ModuleAccess::SECURITY,
        ModuleAccess::SYSTEM,
        ModuleAccess::SETTINGS,
    )->and(ModuleSidebarNavigation::nestedModuleKeys())->not->toContain(ModuleAccess::DASHBOARD);
});

it('renders nested operations navigation in the primary sidebar', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('operations.job-portal.overview'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-module="operations"', false)
        ->assertSee(__('Hiring'), false)
        ->assertSee(__('Vacancies'), false)
        ->assertSee(__('Services'), false);
});

it('renders nested settings navigation without in-page tab strip', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($user)
        ->get(route('settings.appearance'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-module="settings"', false)
        ->assertSee(__('Appearance'), false)
        ->assertDontSee('aria-label="'.__('Settings sections').'"', false);
});

it('renders nested growth center navigation without in-page tab strip', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('growth-center.competitors.index'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-module="growth_center"', false)
        ->assertSee(__('Competitors'), false)
        ->assertDontSee('aria-label="'.__('Growth Center workspaces').'"', false);
});

it('renders nested marketing navigation without duplicate workspace links', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('marketing.dashboard', ['tab' => 'lead-intent']))
        ->assertSuccessful()
        ->assertSee('data-sidebar-module="marketing"', false)
        ->assertSee(__('Dashboard'), false)
        ->assertSee(__('Intelligence'), false)
        ->assertSee(__('Lead Intent'), false)
        ->assertSee(__('Attribution'), false)
        ->assertDontSee('aria-label="'.__('Marketing workspaces').'"', false);
});
