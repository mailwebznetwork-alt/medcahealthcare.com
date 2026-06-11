<?php

use App\Models\User;
use App\ModuleAccess;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
});

it('redirects settings index to appearance', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertRedirect(route('settings.appearance'));
});

it('aliases site architect sections and presets paths', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get('/site-architect/sections')
        ->assertRedirect('/site-architect/section-library');

    $this->actingAs($user)
        ->get('/site-architect/presets')
        ->assertRedirect('/site-architect/block-presets');
});

it('exposes system overview for system module grant', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => array_merge(ModuleAccess::defaultGrants(), [
            ModuleAccess::SYSTEM => true,
            ModuleAccess::SETTINGS => false,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('system.overview'))
        ->assertSuccessful()
        ->assertSee(__('Application'), false);
});

it('preserves legacy settings integrations route', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('settings.integrations'))
        ->assertSuccessful();
});

it('serves growth war room at canonical path', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => array_fill_keys(ModuleAccess::keys(), true),
    ]);

    $this->actingAs($user)
        ->get('/growth-center/war-room')
        ->assertSuccessful();
});

it('adds system to persisted module access keys', function () {
    expect(ModuleAccess::keys())->toContain(ModuleAccess::SYSTEM);
});

it('shows system nav when user has system access', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => array_merge(ModuleAccess::defaultGrants(), [
            ModuleAccess::SYSTEM => true,
            ModuleAccess::SETTINGS => false,
        ]),
    ]);

    $keys = collect($user->visibleSidebarNodes())
        ->pluck('key')
        ->all();

    expect($keys)->toContain(ModuleAccess::SYSTEM);
});
