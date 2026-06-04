<?php

use App\Livewire\Settings\AppearanceSettings;
use App\Models\User;
use App\Services\Theme\ThemeConfigRepository;
use App\Services\Theme\ThemeResolver;
use App\Services\Theme\TypographyScaleResolver;
use App\Support\TypographyTypeScale;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
});

it('emits medca typography css with desktop tablet and mobile breakpoints', function () {
    $css = app(ThemeResolver::class)->typographyCssBlock();

    expect($css)
        ->toContain('--medca-font-heading')
        ->toContain('--medca-text-h1-size')
        ->toContain('--medca-public-min-font-size')
        ->toContain('@media (max-width: 1023px)')
        ->toContain('@media (max-width: 767px)')
        ->not->toContain('clamp(');
});

it('clamps public typography tokens for mobile and desktop body', function () {
    $css = app(ThemeResolver::class)->typographyCssBlock();

    expect($css)
        ->toContain('--medca-public-body-font-size: 1.125rem')
        ->toContain('--medca-text-small-size: 1.125rem')
        ->toMatch('/@media \(max-width: 767px\)[\s\S]*--medca-public-min-font-size: 1rem/')
        ->toMatch('/@media \(max-width: 767px\)[\s\S]*--medca-text-small-size: 1rem/');
});

it('uses saved type scale on the public site after publish', function () {
    $repo = app(ThemeConfigRepository::class);
    $user = User::factory()->create(['role' => 'super_admin']);
    $payload = $repo->defaultTypography();
    $payload['type_scale']['h1']['desktop']['size'] = 3;
    $repo->saveDraftTypography($payload, $user);
    $repo->publishDraft($user);

    $css = app(ThemeResolver::class)->typographyCssBlock();

    expect($css)->toContain('--medca-text-h1-size: 3rem');
});

it('saves editable type scale from appearance settings', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    Livewire::actingAs($admin)
        ->test(AppearanceSettings::class)
        ->call('setTab', 'typography')
        ->set('typography.type_scale.h1.desktop.size', 2.5)
        ->call('saveTypography')
        ->assertHasNoErrors();

    $draft = app(ThemeConfigRepository::class)->draftTypography();

    expect($draft['type_scale']['h1']['desktop']['size'])->toBe(2.5);
});

it('shows editable type scale fields on typography tab', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    Livewire::actingAs($admin)
        ->test(AppearanceSettings::class)
        ->call('setTab', 'typography')
        ->assertSee('Type scale — your sizes', false)
        ->assertSee('wire:model="typography.type_scale.h1.desktop.size"', false);
});

it('builds spec from stored typography', function () {
    $typography = TypographyTypeScale::mergeIntoTypography([
        'heading_font' => 'Inter',
        'body_font' => 'Roboto',
    ]);
    $typography['type_scale']['h2']['mobile']['size'] = 1.5;

    $spec = app(TypographyScaleResolver::class)->fullSpec($typography);

    expect($spec['heading_font'])->toBe('Inter')
        ->and(collect($spec['mobile'])->firstWhere('label', 'H2')['size'])->toContain('1.5rem');
});
