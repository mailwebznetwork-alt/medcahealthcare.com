<?php

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('denies all modules when module_access is null for non-super-admin roles', function (string $role) {
    $user = User::factory()->create([
        'role' => $role,
        'module_access' => null,
        'email_verified_at' => now(),
    ]);

    expect($user->hasModuleAccess(ModuleAccess::MARKETING))->toBeFalse()
        ->and($user->hasModuleAccess(ModuleAccess::DASHBOARD))->toBeFalse();
})->with(['viewer', 'editor', 'manager', 'admin']);

it('grants all modules when module_access is null for super_admin role', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => null,
        'email_verified_at' => now(),
    ]);

    expect($user->hasModuleAccess(ModuleAccess::SETTINGS))->toBeTrue();
});

it('blocks admin integrations api without settings module grant', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
        'module_access' => array_merge(
            array_fill_keys(ModuleAccess::keys(), true),
            [ModuleAccess::SETTINGS => false]
        ),
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.settings.integrations.index'))
        ->assertForbidden();
});

it('allows admin integrations api with settings module grant', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
        'module_access' => array_fill_keys(ModuleAccess::keys(), true),
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.settings.integrations.index'))
        ->assertOk();
});

it('redirects legacy marketing root to canonical dashboard', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => array_fill_keys(ModuleAccess::keys(), true),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/marketing')
        ->assertRedirect('/marketing/dashboard');
});

it('serves marketing canonical section routes', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => array_fill_keys(ModuleAccess::keys(), true),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('marketing.campaigns'))
        ->assertRedirect(route('marketing.dashboard').'#marketing-campaigns');

    $this->actingAs($user)
        ->get(route('marketing.attribution'))
        ->assertRedirect(route('marketing.intelligence', ['tab' => 'attribution']));
});

it('canonicalizes growth readiness away from competitor tab query', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ['growth_center' => true],
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/growth-center/competitors?tab=readiness')
        ->assertRedirect(route('growth-center.readiness'));
});

it('canonicalizes growth war room dashboard path', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ['growth_center' => true],
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/growth-center/war-room/dashboard')
        ->assertRedirect('/growth-center/war-room');
});
