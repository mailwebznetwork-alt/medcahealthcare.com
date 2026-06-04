<?php

use App\Models\Page;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServiceSeoOwnership;

function phaseBArchitectUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::SITE_ARCHITECT])
            ->all(),
    ]);
}

it('exposes block content schema config for hero-home', function () {
    expect(config('block_content_schemas.blocks.hero-home'))->toBeArray()
        ->and(config('block_content_schemas.blocks.hero-home.headline.default'))->toContain('home healthcare');
});

it('documents contact form ownership without global content forms', function () {
    expect(config('contact_forms.ownership.global_content'))->toBe('constants_only')
        ->and(config('contact_forms.ownership.submission'))->toBe('public.leads.store')
        ->and(config('contact_forms.presentation_blocks'))->toContain('form-callback');
});

it('marks section library deprecated in platform composition config', function () {
    expect(config('platform_composition.section_library_deprecated'))->toBeTrue();
});

it('detects when linked page SEO is canonical over service SEO', function () {
    $page = Page::factory()->create([
        'meta_title' => 'Canonical page title',
        'meta_description' => 'Canonical description',
    ]);

    expect(ServiceSeoOwnership::pageSeoOverridesService($page))->toBeTrue();

    $empty = Page::withoutEvents(fn () => Page::factory()->create([
        'meta_title' => null,
        'meta_description' => null,
        'h1' => null,
    ]));
    expect(ServiceSeoOwnership::pageSeoOverridesService($empty->fresh()))->toBeFalse();
});

it('loads pages editor with production preview route when editing', function () {
    $page = Page::factory()->create();

    $this->actingAs(phaseBArchitectUser())
        ->get(route('site-architect.pages.index', ['edit' => $page->id]))
        ->assertOk()
        ->assertSee(route('site-architect.pages.preview', $page), false);
});
