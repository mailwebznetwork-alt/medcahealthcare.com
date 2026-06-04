<?php

use App\Models\User;
use App\ModuleAccess;
use App\Support\SiteArchitectNavigation;

it('shows simplified blocks nav for editors without deploy or advanced tabs', function () {
    $user = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    expect(SiteArchitectNavigation::showsFullWorkspaceNav($user))->toBeFalse();

    $this->actingAs($user)
        ->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertSee(__('Section Content'), false)
        ->assertSee(__('Style Templates'), false)
        ->assertDontSee(__('Blocks Factory'), false)
        ->assertDontSee(__('Legacy Sections'), false)
        ->assertDontSee(__('Module Builder'), false)
        ->assertDontSee(__('Blueprint Builder'), false)
        ->assertDontSee(__('Packages'), false);
});

it('shows full site architect nav for admin users', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    expect(SiteArchitectNavigation::showsFullWorkspaceNav($user))->toBeTrue();

    $this->actingAs($user)
        ->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertSee(__('Legacy Sections'), false)
        ->assertSee(__('Module Builder'), false)
        ->assertSee(__('Blueprint Builder'), false)
        ->assertSee(__('Packages'), false);
});

it('preserves legacy section and preset route aliases', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($admin)
        ->followingRedirects()
        ->get(route('site-architect.sections.index'))
        ->assertSuccessful()
        ->assertSee(__('Legacy / Backward Compatibility'), false);

    $editor = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($editor)
        ->followingRedirects()
        ->get(route('site-architect.presets.index'))
        ->assertSuccessful()
        ->assertSee(__('Templates'), false);
});

it('does not show deployment hub on blocks studio', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.block-studio.index'))
        ->assertSuccessful()
        ->assertDontSee(__('MarkOnMinds Deployment Engine'), false)
        ->assertDontSee(__('Deploy shortcuts'), false);
});

it('shows compose journey guidance on pages workspace', function () {
    $user = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertSee(__('Quick path: Page → Section → Content → Preview → Live'), false);
});

it('shows deploy shortcuts only on blueprint builder', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.blueprint-builder.index'))
        ->assertSuccessful()
        ->assertSee(__('Deploy shortcuts'), false);
});
